package main

import (
	"archive/tar"
	"compress/gzip"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"runtime"
	"strings"
	"sync"
	"time"
)

type ServerRecord struct {
	ID             string            `json:"id"`
	Name           string            `json:"name"`
	Image          string            `json:"image"`
	ContainerID    string            `json:"container_id"`
	Status         string            `json:"status"` // installing, stopped, running, suspended
	Env            map[string]string `json:"env"`
	Limits         ServerLimits      `json:"limits"`
	Ports          []PortMapping     `json:"ports"`
	StartupCommand string            `json:"startup_command"`
	ServerDir      string            `json:"server_dir"`
	LogBuffer      []string          `json:"log_buffer"`
	wsClients      map[*WSConn]bool
	stdinWriter    io.Writer
	mu             sync.RWMutex
}

type Daemon struct {
	dc         *DockerClient
	dataDir    string
	servers    map[string]*ServerRecord
	mu         sync.RWMutex
	startTime  time.Time
}

func NewDaemon(dataDir string) *Daemon {
	os.MkdirAll(dataDir, 0755)
	return &Daemon{
		dc:        NewDockerClient(),
		dataDir:   dataDir,
		servers:   make(map[string]*ServerRecord),
		startTime: time.Now(),
	}
}

func (d *Daemon) handleSystem(w http.ResponseWriter, r *http.Request) {
	d.mu.RLock()
	serverCount := len(d.servers)
	d.mu.RUnlock()

	var m runtime.MemStats
	runtime.ReadMemStats(&m)

	resp := map[string]interface{}{
		"daemon_version": "2.0.0-nextgen",
		"uptime_seconds": time.Since(d.startTime).Seconds(),
		"cpu_cores":      runtime.NumCPU(),
		"memory_alloc_mb": m.Alloc / 1024 / 1024,
		"server_count":   serverCount,
		"status":         "healthy",
		"docker_socket":  "/var/run/docker.sock",
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(resp)
}

func (d *Daemon) handleServers(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	if r.Method == "GET" {
		d.mu.RLock()
		defer d.mu.RUnlock()
		var list []*ServerRecord
		for _, s := range d.servers {
			list = append(list, s)
		}
		json.NewEncoder(w).Encode(list)
		return
	}

	if r.Method == "POST" {
		var req ServerCreateReq
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
			http.Error(w, `{"error":"invalid json"}`, 400)
			return
		}
		if req.ID == "" {
			req.ID = fmt.Sprintf("srv_%d", time.Now().UnixNano())
		}
		serverDir := filepath.Join(d.dataDir, req.ID)
		os.MkdirAll(serverDir, 0755)

		containerID, err := d.dc.CreateContainer(req, serverDir)
		if err != nil {
			http.Error(w, fmt.Sprintf(`{"error":"failed to create container: %s"}`, err.Error()), 500)
			return
		}

		srv := &ServerRecord{
			ID:             req.ID,
			Name:           req.Name,
			Image:          req.Image,
			ContainerID:    containerID,
			Status:         "stopped",
			Env:            req.Env,
			Limits:         req.Limits,
			Ports:          req.Ports,
			StartupCommand: req.StartupCommand,
			ServerDir:      serverDir,
			wsClients:      make(map[*WSConn]bool),
		}

		d.mu.Lock()
		d.servers[req.ID] = srv
		d.mu.Unlock()

		w.WriteHeader(201)
		json.NewEncoder(w).Encode(srv)
		return
	}
}

func (d *Daemon) handleServerRoute(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Methods", "GET, POST, DELETE, OPTIONS")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
	if r.Method == "OPTIONS" {
		w.WriteHeader(200)
		return
	}

	// Path: /api/servers/{id}/...
	parts := strings.Split(strings.Trim(r.URL.Path, "/"), "/")
	if len(parts) < 3 {
		http.Error(w, `{"error":"not found"}`, 404)
		return
	}
	serverID := parts[2]

	d.mu.RLock()
	srv, exists := d.servers[serverID]
	d.mu.RUnlock()
	if !exists {
		http.Error(w, `{"error":"server not found"}`, 404)
		return
	}

	if len(parts) == 3 {
		// /api/servers/{id}
		if r.Method == "GET" {
			w.Header().Set("Content-Type", "application/json")
			json.NewEncoder(w).Encode(srv)
			return
		}
		if r.Method == "DELETE" {
			d.dc.KillContainer(srv.ContainerID)
			d.dc.RemoveContainer(srv.ContainerID)
			os.RemoveAll(srv.ServerDir)
			d.mu.Lock()
			delete(d.servers, serverID)
			d.mu.Unlock()
			w.Header().Set("Content-Type", "application/json")
			json.NewEncoder(w).Encode(map[string]string{"status": "deleted"})
			return
		}
	}

	sub := parts[3]
	switch sub {
	case "power":
		var req struct {
			Action string `json:"action"` // start, stop, restart, kill
		}
		json.NewDecoder(r.Body).Decode(&req)
		var err error
		switch req.Action {
		case "start":
			err = d.dc.StartContainer(srv.ContainerID)
			if err == nil {
				srv.Status = "running"
				go d.attachConsoleStream(srv)
			}
		case "stop":
			err = d.dc.StopContainer(srv.ContainerID)
			if err == nil {
				srv.Status = "stopped"
			}
		case "kill":
			err = d.dc.KillContainer(srv.ContainerID)
			if err == nil {
				srv.Status = "stopped"
			}
		case "restart":
			d.dc.StopContainer(srv.ContainerID)
			err = d.dc.StartContainer(srv.ContainerID)
			if err == nil {
				srv.Status = "running"
				go d.attachConsoleStream(srv)
			}
		}
		if err != nil {
			http.Error(w, fmt.Sprintf(`{"error":"%s"}`, err.Error()), 500)
			return
		}
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]string{"status": "ok", "state": srv.Status})

	case "stats":
		stats, err := d.dc.GetContainerStats(srv.ContainerID)
		if err != nil {
			http.Error(w, fmt.Sprintf(`{"error":"%s"}`, err.Error()), 500)
			return
		}
		// Calculate disk size
		var diskSize uint64
		filepath.Walk(srv.ServerDir, func(_ string, info os.FileInfo, err error) error {
			if err == nil && info != nil && !info.IsDir() {
				diskSize += uint64(info.Size())
			}
			return nil
		})
		stats.DiskBytes = diskSize
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(stats)

	case "resize":
		var req struct {
			MemoryMB int64  `json:"memory_mb"`
			CPULimit int64  `json:"cpu_limit"`
			CpuSet   string `json:"cpuset"`
		}
		json.NewDecoder(r.Body).Decode(&req)
		err := d.dc.UpdateContainerLimits(srv.ContainerID, req.MemoryMB, req.CPULimit*10000000)
		if err != nil {
			http.Error(w, fmt.Sprintf(`{"error":"%s"}`, err.Error()), 500)
			return
		}
		srv.Limits.MemoryMB = req.MemoryMB
		srv.Limits.CPULimit = req.CPULimit
		srv.Limits.CpuSet = req.CpuSet
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]string{"status": "resized"})

	case "install":
		var req struct {
			Image  string            `json:"image"`
			Script string            `json:"script"`
			Env    map[string]string `json:"env"`
		}
		json.NewDecoder(r.Body).Decode(&req)
		image := req.Image
		if image == "" {
			image = "alpine:latest"
		}
		output, err := d.dc.RunInstallScript(image, req.Script, srv.ServerDir, req.Env)
		if err != nil {
			http.Error(w, fmt.Sprintf(`{"error":"%s", "output":"%s"}`, err.Error(), output), 500)
			return
		}
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]string{"status": "installed", "output": output})

	case "backup":
		tarName := fmt.Sprintf("backup_%d.tar.gz", time.Now().Unix())
		tarPath := filepath.Join(srv.ServerDir, "..", tarName)
		f, err := os.Create(tarPath)
		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		gw := gzip.NewWriter(f)
		tw := tar.NewWriter(gw)

		filepath.Walk(srv.ServerDir, func(path string, info os.FileInfo, err error) error {
			if err != nil || info == nil {
				return nil
			}
			rel, _ := filepath.Rel(srv.ServerDir, path)
			if rel == "." {
				return nil
			}
			header, _ := tar.FileInfoHeader(info, "")
			header.Name = rel
			tw.WriteHeader(header)
			if !info.IsDir() {
				src, _ := os.Open(path)
				io.Copy(tw, src)
				src.Close()
			}
			return nil
		})
		tw.Close()
		gw.Close()
		f.Close()

		fi, _ := os.Stat(tarPath)
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]interface{}{
			"filename": tarName,
			"path":     tarPath,
			"size":     fi.Size(),
		})

	case "files":
		relPath := r.URL.Query().Get("path")
		fullPath := filepath.Join(srv.ServerDir, filepath.Clean("/"+relPath))

		if len(parts) > 4 && parts[4] == "contents" {
			// Read file content
			data, err := os.ReadFile(fullPath)
			if err != nil {
				http.Error(w, err.Error(), 404)
				return
			}
			w.Header().Set("Content-Type", "text/plain; charset=utf-8")
			w.Write(data)
			return
		}

		if len(parts) > 4 && parts[4] == "write" {
			var writeReq struct {
				Path    string `json:"path"`
				Content string `json:"content"`
			}
			json.NewDecoder(r.Body).Decode(&writeReq)
			target := filepath.Join(srv.ServerDir, filepath.Clean("/"+writeReq.Path))
			os.MkdirAll(filepath.Dir(target), 0755)
			if err := os.WriteFile(target, []byte(writeReq.Content), 0644); err != nil {
				http.Error(w, err.Error(), 500)
				return
			}
			w.Header().Set("Content-Type", "application/json")
			json.NewEncoder(w).Encode(map[string]string{"status": "saved"})
			return
		}

		if r.Method == "DELETE" {
			os.RemoveAll(fullPath)
			w.Header().Set("Content-Type", "application/json")
			json.NewEncoder(w).Encode(map[string]string{"status": "deleted"})
			return
		}

		// List directory
		entries, err := os.ReadDir(fullPath)
		if err != nil {
			entries, _ = os.ReadDir(srv.ServerDir)
		}
		type FileItem struct {
			Name     string `json:"name"`
			Size     int64  `json:"size"`
			IsDir    bool   `json:"is_dir"`
			Modified string `json:"modified"`
		}
		var files []FileItem
		for _, e := range entries {
			fi, _ := e.Info()
			size := int64(0)
			mod := ""
			if fi != nil {
				size = fi.Size()
				mod = fi.ModTime().Format(time.RFC3339)
			}
			files = append(files, FileItem{
				Name:     e.Name(),
				Size:     size,
				IsDir:    e.IsDir(),
				Modified: mod,
			})
		}
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(files)

	case "ws":
		ws, err := UpgradeWS(w, r)
		if err != nil {
			return
		}
		defer ws.Close()

		srv.mu.Lock()
		srv.wsClients[ws] = true
		// Send backbuffered logs
		for _, l := range srv.LogBuffer {
			ws.WriteText(l)
		}
		srv.mu.Unlock()

		defer func() {
			srv.mu.Lock()
			delete(srv.wsClients, ws)
			srv.mu.Unlock()
		}()

		for {
			text, err := ws.ReadText()
			if err != nil {
				break
			}
			var msg struct {
				Event   string `json:"event"`
				Command string `json:"command"`
			}
			if err := json.Unmarshal([]byte(text), &msg); err == nil && msg.Command != "" {
				text = msg.Command
			}
			if !strings.HasSuffix(text, "\n") {
				text += "\n"
			}
			srv.mu.RLock()
			writer := srv.stdinWriter
			srv.mu.RUnlock()
			if writer != nil {
				writer.Write([]byte(text))
			}
		}
	}
}

func (d *Daemon) attachConsoleStream(srv *ServerRecord) {
	conn, reader, err := d.dc.AttachContainerIO(srv.ContainerID)
	if err != nil {
		log.Printf("Failed to attach to container %s: %v", srv.ContainerID, err)
		return
	}
	defer conn.Close()

	srv.mu.Lock()
	srv.stdinWriter = conn
	srv.mu.Unlock()

	buf := make([]byte, 4096)
	for {
		n, err := reader.Read(buf)
		if n > 0 {
			chunk := string(buf[:n])
			srv.mu.Lock()
			srv.LogBuffer = append(srv.LogBuffer, chunk)
			if len(srv.LogBuffer) > 500 {
				srv.LogBuffer = srv.LogBuffer[len(srv.LogBuffer)-500:]
			}
			// Broadcast to all active websockets
			for client := range srv.wsClients {
				client.WriteText(chunk)
			}
			srv.mu.Unlock()
		}
		if err != nil {
			break
		}
	}

	srv.mu.Lock()
	srv.stdinWriter = nil
	srv.mu.Unlock()
}

func main() {
	port := os.Getenv("DAEMON_PORT")
	if port == "" {
		port = "8081"
	}
	dataDir := os.Getenv("DAEMON_DATA_DIR")
	if dataDir == "" {
		dataDir = "/srv/daemon/servers"
	}

	daemon := NewDaemon(dataDir)

	mux := http.NewServeMux()
	mux.HandleFunc("/api/system", daemon.handleSystem)
	mux.HandleFunc("/api/servers", daemon.handleServers)
	mux.HandleFunc("/api/servers/", daemon.handleServerRoute)

	log.Printf("[Daemon] NextGen Wings Daemon listening on :%s (Data: %s)", port, dataDir)
	if err := http.ListenAndServe(":"+port, mux); err != nil {
		log.Fatalf("Daemon failed: %v", err)
	}
}
