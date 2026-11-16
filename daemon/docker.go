package main

import (
	"bufio"
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"
)

type DockerClient struct {
	client *http.Client
}

func NewDockerClient() *DockerClient {
	return &DockerClient{
		client: &http.Client{
			Timeout: 0, // No global timeout for streaming
			Transport: &http.Transport{
				DialContext: func(ctx context.Context, _, _ string) (net.Conn, error) {
					return (&net.Dialer{}).DialContext(ctx, "unix", "/var/run/docker.sock")
				},
			},
		},
	}
}

type ServerCreateReq struct {
	ID             string            `json:"id"`
	Name           string            `json:"name"`
	Image          string            `json:"image"`
	Env            map[string]string `json:"env"`
	Limits         ServerLimits      `json:"limits"`
	Ports          []PortMapping     `json:"ports"`
	StartupCommand string            `json:"startup_command"`
}

type ServerLimits struct {
	MemoryMB int64  `json:"memory_mb"`
	CPULimit int64  `json:"cpu_limit"` // In percentage, e.g. 100 = 1 core
	CpuSet   string `json:"cpuset"`
}

type PortMapping struct {
	ContainerPort int    `json:"container_port"`
	HostPort      int    `json:"host_port"`
	Protocol      string `json:"protocol"` // tcp or udp
}

type ServerStats struct {
	CPUPercent     float64 `json:"cpu_percent"`
	MemoryBytes    uint64  `json:"memory_bytes"`
	MemoryLimit    uint64  `json:"memory_limit"`
	DiskBytes      uint64  `json:"disk_bytes"`
	NetworkRxBytes uint64  `json:"network_rx_bytes"`
	NetworkTxBytes uint64  `json:"network_tx_bytes"`
	State          string  `json:"state"`
}

func (dc *DockerClient) CreateContainer(req ServerCreateReq, serverDir string) (string, error) {
	// Prepare environment
	var envList []string
	for k, v := range req.Env {
		envList = append(envList, fmt.Sprintf("%s=%s", k, v))
	}
	envList = append(envList, "STARTUP="+req.StartupCommand)

	// Prepare cmd
	cmd := []string{"/bin/sh", "-c", req.StartupCommand}

	// Prepare port bindings
	exposedPorts := make(map[string]struct{})
	portBindings := make(map[string][]map[string]string)
	for _, p := range req.Ports {
		proto := p.Protocol
		if proto == "" {
			proto = "tcp"
		}
		key := fmt.Sprintf("%d/%s", p.ContainerPort, proto)
		exposedPorts[key] = struct{}{}
		portBindings[key] = []map[string]string{
			{"HostPort": fmt.Sprintf("%d", p.HostPort)},
		}
	}

	nanoCPUs := req.Limits.CPULimit * 10000000
	if nanoCPUs <= 0 {
		nanoCPUs = 1000000000 // 1 core default
	}
	memBytes := req.Limits.MemoryMB * 1024 * 1024
	if memBytes <= 0 {
		memBytes = 1024 * 1024 * 1024 // 1GB default
	}

	payload := map[string]interface{}{
		"Image":        req.Image,
		"Cmd":          cmd,
		"Env":          envList,
		"OpenStdin":    true,
		"Tty":          true,
		"WorkingDir":   "/home/container",
		"ExposedPorts": exposedPorts,
		"HostConfig": map[string]interface{}{
			"Binds": []string{
				fmt.Sprintf("%s:/home/container:rw", serverDir),
			},
			"PortBindings": portBindings,
			"Memory":       memBytes,
			"NanoCPUs":     nanoCPUs,
			"CpusetCpus":   req.Limits.CpuSet,
			"RestartPolicy": map[string]string{
				"Name": "unless-stopped",
			},
		},
	}

	b, _ := json.Marshal(payload)
	name := fmt.Sprintf("gamepanel_%s", req.ID)
	// Delete any existing container with this name first
	dc.RemoveContainer(name)

	resp, err := dc.client.Post(fmt.Sprintf("http://localhost/containers/create?name=%s", name), "application/json", bytes.NewReader(b))
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	respBody, _ := io.ReadAll(resp.Body)
	if resp.StatusCode != 201 {
		return "", fmt.Errorf("create failed (%d): %s", resp.StatusCode, string(respBody))
	}

	var res struct {
		ID string `json:"Id"`
	}
	if err := json.Unmarshal(respBody, &res); err != nil {
		return "", err
	}
	return res.ID, nil
}

func (dc *DockerClient) StartContainer(id string) error {
	resp, err := dc.client.Post(fmt.Sprintf("http://localhost/containers/%s/start", id), "application/json", nil)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode != 204 && resp.StatusCode != 304 {
		b, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("start failed (%d): %s", resp.StatusCode, string(b))
	}
	return nil
}

func (dc *DockerClient) StopContainer(id string) error {
	resp, err := dc.client.Post(fmt.Sprintf("http://localhost/containers/%s/stop?t=10", id), "application/json", nil)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode != 204 && resp.StatusCode != 304 {
		b, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("stop failed (%d): %s", resp.StatusCode, string(b))
	}
	return nil
}

func (dc *DockerClient) KillContainer(id string) error {
	resp, err := dc.client.Post(fmt.Sprintf("http://localhost/containers/%s/kill", id), "application/json", nil)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	return nil
}

func (dc *DockerClient) RemoveContainer(id string) error {
	req, _ := http.NewRequest("DELETE", fmt.Sprintf("http://localhost/containers/%s?force=true&v=true", id), nil)
	resp, err := dc.client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	return nil
}

func (dc *DockerClient) UpdateContainerLimits(id string, memoryMB int64, nanoCPUs int64) error {
	payload := map[string]interface{}{}
	if memoryMB > 0 {
		payload["Memory"] = memoryMB * 1024 * 1024
	}
	if nanoCPUs > 0 {
		payload["NanoCPUs"] = nanoCPUs
	}
	b, _ := json.Marshal(payload)
	resp, err := dc.client.Post(fmt.Sprintf("http://localhost/containers/%s/update", id), "application/json", bytes.NewReader(b))
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode != 200 {
		respBody, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("update limits failed (%d): %s", resp.StatusCode, string(respBody))
	}
	return nil
}

func (dc *DockerClient) GetContainerStats(id string) (*ServerStats, error) {
	resp, err := dc.client.Get(fmt.Sprintf("http://localhost/containers/%s/stats?stream=false", id))
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != 200 {
		return &ServerStats{State: "stopped"}, nil
	}

	var data map[string]interface{}
	if err := json.NewDecoder(resp.Body).Decode(&data); err != nil {
		return nil, err
	}

	stats := &ServerStats{State: "running"}

	// Memory
	if mem, ok := data["memory_stats"].(map[string]interface{}); ok {
		if usage, ok := mem["usage"].(float64); ok {
			stats.MemoryBytes = uint64(usage)
		}
		if limit, ok := mem["limit"].(float64); ok {
			stats.MemoryLimit = uint64(limit)
		}
	}

	// CPU calculation
	cpuStats, ok1 := data["cpu_stats"].(map[string]interface{})
	precpuStats, ok2 := data["precpu_stats"].(map[string]interface{})
	if ok1 && ok2 {
		cpuUsage := getFloat(cpuStats, "cpu_usage", "total_usage")
		preCPUUsage := getFloat(precpuStats, "cpu_usage", "total_usage")
		systemUsage := getFloat(cpuStats, "system_cpu_usage")
		preSystemUsage := getFloat(precpuStats, "system_cpu_usage")

		cpuDelta := cpuUsage - preCPUUsage
		systemDelta := systemUsage - preSystemUsage

		if systemDelta > 0 && cpuDelta > 0 {
			onlineCPUs := 1.0
			if o, ok := cpuStats["online_cpus"].(float64); ok && o > 0 {
				onlineCPUs = o
			}
			stats.CPUPercent = (cpuDelta / systemDelta) * onlineCPUs * 100.0
		}
	}

	// Network
	if nets, ok := data["networks"].(map[string]interface{}); ok {
		for _, v := range nets {
			if netMap, ok := v.(map[string]interface{}); ok {
				stats.NetworkRxBytes += uint64(getFloat(netMap, "rx_bytes"))
				stats.NetworkTxBytes += uint64(getFloat(netMap, "tx_bytes"))
			}
		}
	}

	return stats, nil
}

func getFloat(m map[string]interface{}, keys ...string) float64 {
	var curr interface{} = m
	for _, k := range keys {
		if curMap, ok := curr.(map[string]interface{}); ok {
			curr = curMap[k]
		} else {
			return 0
		}
	}
	if f, ok := curr.(float64); ok {
		return f
	}
	return 0
}

func (dc *DockerClient) AttachContainerIO(containerID string) (net.Conn, *bufio.Reader, error) {
	conn, err := net.Dial("unix", "/var/run/docker.sock")
	if err != nil {
		return nil, nil, err
	}

	req := fmt.Sprintf("POST /containers/%s/attach?stdin=1&stdout=1&stderr=1&stream=1 HTTP/1.1\r\nHost: localhost\r\nUpgrade: tcp\r\nConnection: Upgrade\r\n\r\n", containerID)
	if _, err := conn.Write([]byte(req)); err != nil {
		conn.Close()
		return nil, nil, err
	}

	reader := bufio.NewReader(conn)
	// Read response status
	line, err := reader.ReadString('\n')
	if err != nil || (!strings.Contains(line, "200") && !strings.Contains(line, "101")) {
		conn.Close()
		return nil, nil, fmt.Errorf("attach failed with status: %s", line)
	}

	// Read remaining HTTP headers until blank line
	for {
		headerLine, err := reader.ReadString('\n')
		if err != nil {
			conn.Close()
			return nil, nil, err
		}
		if strings.TrimSpace(headerLine) == "" {
			break
		}
	}

	return conn, reader, nil
}

func (dc *DockerClient) RunInstallScript(image string, script string, serverDir string, env map[string]string) (string, error) {
	os.MkdirAll(serverDir, 0755)

	scriptFile := filepath.Join(serverDir, ".install.sh")
	os.WriteFile(scriptFile, []byte("#!/bin/sh\nset -e\ncd /mnt/server\n"+script), 0755)
	defer os.Remove(scriptFile)

	var envList []string
	for k, v := range env {
		envList = append(envList, fmt.Sprintf("%s=%s", k, v))
	}

	createPayload := map[string]interface{}{
		"Image":      image,
		"Cmd":        []string{"/bin/sh", "/mnt/server/.install.sh"},
		"Env":        envList,
		"WorkingDir": "/mnt/server",
		"HostConfig": map[string]interface{}{
			"Binds": []string{
				fmt.Sprintf("%s:/mnt/server:rw", serverDir),
			},
		},
	}
	b, _ := json.Marshal(createPayload)
	resp, err := dc.client.Post("http://localhost/containers/create?name=gamepanel_installer_"+fmt.Sprintf("%d", time.Now().UnixNano()), "application/json", bytes.NewReader(b))
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()
	respBody, _ := io.ReadAll(resp.Body)
	var res struct {
		ID string `json:"Id"`
	}
	json.Unmarshal(respBody, &res)
	if res.ID == "" {
		return "", fmt.Errorf("failed to create installer container: %s", string(respBody))
	}
	defer dc.RemoveContainer(res.ID)

	// Start container
	if err := dc.StartContainer(res.ID); err != nil {
		return "", err
	}

	// Wait for container to exit
	waitResp, err := dc.client.Post(fmt.Sprintf("http://localhost/containers/%s/wait", res.ID), "application/json", nil)
	if err != nil {
		return "", err
	}
	defer waitResp.Body.Close()

	// Get logs
	logsResp, err := dc.client.Get(fmt.Sprintf("http://localhost/containers/%s/logs?stdout=1&stderr=1", res.ID))
	if err != nil {
		return "", err
	}
	defer logsResp.Body.Close()
	logs, _ := io.ReadAll(logsResp.Body)
	return string(logs), nil
}
