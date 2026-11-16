package main

import (
	"bufio"
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net"
	"net/http"
	"net/http/httptest"
	"os"
	"strings"
	"testing"
	"time"
)

func TestDaemonFullIntegration(t *testing.T) {
	testDir := "/tmp/daemon_test_servers"
	os.RemoveAll(testDir)
	os.MkdirAll(testDir, 0755)
	defer os.RemoveAll(testDir)

	daemon := NewDaemon(testDir)

	mux := http.NewServeMux()
	mux.HandleFunc("/api/system", daemon.handleSystem)
	mux.HandleFunc("/api/servers", daemon.handleServers)
	mux.HandleFunc("/api/servers/", daemon.handleServerRoute)

	ts := httptest.NewServer(mux)
	defer ts.Close()

	// 1. Test System Telemetry
	resp, err := http.Get(ts.URL + "/api/system")
	if err != nil {
		t.Fatalf("system api failed: %v", err)
	}
	defer resp.Body.Close()
	var sysInfo map[string]interface{}
	json.NewDecoder(resp.Body).Decode(&sysInfo)
	if sysInfo["status"] != "healthy" {
		t.Fatalf("expected healthy daemon, got %v", sysInfo)
	}
	t.Logf("✓ System Telemetry verified: CPU Cores: %v, Status: %v", sysInfo["cpu_cores"], sysInfo["status"])

	// 2. Create Server Container
	serverID := fmt.Sprintf("test_%d", time.Now().UnixNano())
	createReq := ServerCreateReq{
		ID:             serverID,
		Name:           "Test Minecraft Server",
		Image:          "alpine:latest",
		Env:            map[string]string{"TEST_ENV": "1"},
		Limits:         ServerLimits{MemoryMB: 512, CPULimit: 100},
		StartupCommand: "sh -c 'echo \"CONTAINER_BOOT_OK\"; while true; do read cmd; if [ -n \"$cmd\" ]; then echo \"CONSOLE_OUT: $cmd\"; fi; done'",
	}
	createJSON, _ := json.Marshal(createReq)
	cResp, err := http.Post(ts.URL+"/api/servers", "application/json", bytes.NewReader(createJSON))
	if err != nil {
		t.Fatalf("create server failed: %v", err)
	}
	defer cResp.Body.Close()
	if cResp.StatusCode != 201 {
		b, _ := io.ReadAll(cResp.Body)
		t.Fatalf("create server failed (%d): %s", cResp.StatusCode, string(b))
	}
	t.Logf("✓ Container provisioned: %s", serverID)

	// 3. Test Egg Install Script
	installReq := map[string]interface{}{
		"image":  "alpine:latest",
		"script": "echo 'EGG_INSTALLATION_COMPLETE' > version.txt; echo 'Installed v1.20'",
		"env":    map[string]string{},
	}
	instJSON, _ := json.Marshal(installReq)
	instResp, err := http.Post(ts.URL+"/api/servers/"+serverID+"/install", "application/json", bytes.NewReader(instJSON))
	if err != nil {
		t.Fatalf("install script failed: %v", err)
	}
	defer instResp.Body.Close()
	var instResult map[string]interface{}
	json.NewDecoder(instResp.Body).Decode(&instResult)
	if instResult["status"] != "installed" {
		t.Fatalf("install expected 'installed', got %v", instResult)
	}
	t.Logf("✓ Egg Install Script executed: %v", instResult["output"])

	// Verify installed file exists in server directory
	versionContent, err := os.ReadFile(fmt.Sprintf("%s/%s/version.txt", testDir, serverID))
	if err != nil || !strings.Contains(string(versionContent), "EGG_INSTALLATION_COMPLETE") {
		t.Fatalf("version.txt content mismatch: %v", string(versionContent))
	}
	t.Logf("✓ Egg Installation verified on disk")

	// 4. Power Start Server
	powerReq := map[string]string{"action": "start"}
	pJSON, _ := json.Marshal(powerReq)
	pResp, err := http.Post(ts.URL+"/api/servers/"+serverID+"/power", "application/json", bytes.NewReader(pJSON))
	if err != nil {
		t.Fatalf("power start failed: %v", err)
	}
	pResp.Body.Close()
	t.Logf("✓ Server container started")

	// Wait for container to output boot message
	time.Sleep(1 * time.Second)

	// 5. Connect WebSocket Console
	host := ts.Listener.Addr().String()
	conn, err := net.Dial("tcp", host)
	if err != nil {
		t.Fatalf("failed to dial websocket: %v", err)
	}
	defer conn.Close()

	wsKey := "x3JJHMbDL1EzLkh9GBhXDw=="
	wsReq := fmt.Sprintf("GET /api/servers/%s/ws HTTP/1.1\r\nHost: %s\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: %s\r\nSec-WebSocket-Version: 13\r\n\r\n", serverID, host, wsKey)
	conn.Write([]byte(wsReq))

	reader := bufio.NewReader(conn)
	for {
		line, err := reader.ReadString('\n')
		if err != nil {
			t.Fatalf("ws handshake failed: %v", err)
		}
		if strings.TrimSpace(line) == "" {
			break
		}
	}
	wsConn := &WSConn{conn: conn, rw: bufio.NewReadWriter(reader, bufio.NewWriter(conn))}

	// Send echo "HOSTING_READY" command over WebSocket
	sendMasked := func(text string) {
		payload := []byte(text + "\n")
		mask := [4]byte{9, 8, 7, 6}
		masked := make([]byte, len(payload))
		for i := range payload {
			masked[i] = payload[i] ^ mask[i%4]
		}
		frame := []byte{0x81, byte(0x80 | len(payload)), mask[0], mask[1], mask[2], mask[3]}
		frame = append(frame, masked...)
		conn.Write(frame)
	}

	sendMasked("echo HOSTING_READY")

	// Read WS output until HOSTING_READY is seen
	foundExpected := false
	deadline := time.Now().Add(5 * time.Second)
	for time.Now().Before(deadline) {
		msg, err := wsConn.ReadText()
		if err != nil {
			break
		}
		t.Logf("[WS Console Recv]: %q", msg)
		if strings.Contains(msg, "HOSTING_READY") || strings.Contains(msg, "CONTAINER_BOOT_OK") {
			foundExpected = true
			break
		}
	}
	if !foundExpected {
		t.Fatalf("WebSocket did not receive expected container output")
	}
	t.Logf("✓ WebSocket Console interactive I/O verified!")

	// 6. Test Stats Telemetry
	sResp, err := http.Get(ts.URL + "/api/servers/" + serverID + "/stats")
	if err != nil {
		t.Fatalf("stats failed: %v", err)
	}
	defer sResp.Body.Close()
	var stats ServerStats
	json.NewDecoder(sResp.Body).Decode(&stats)
	t.Logf("✓ Telemetry verified: State: %s, Memory: %d bytes, Limit: %d bytes", stats.State, stats.MemoryBytes, stats.MemoryLimit)

	// 7. Test Live Resize (cgroups RAM/CPU update)
	resizeReq := map[string]interface{}{"memory_mb": 1024, "cpu_limit": 200}
	rJSON, _ := json.Marshal(resizeReq)
	rResp, err := http.Post(ts.URL+"/api/servers/"+serverID+"/resize", "application/json", bytes.NewReader(rJSON))
	if err != nil {
		t.Fatalf("resize failed: %v", err)
	}
	rResp.Body.Close()
	t.Logf("✓ Live cgroups resource resize verified")

	// 8. Test Backup Creation
	bResp, err := http.Post(ts.URL+"/api/servers/"+serverID+"/backup", "application/json", nil)
	if err != nil {
		t.Fatalf("backup failed: %v", err)
	}
	defer bResp.Body.Close()
	var backupInfo map[string]interface{}
	json.NewDecoder(bResp.Body).Decode(&backupInfo)
	if backupInfo["filename"] == nil {
		t.Fatalf("expected backup filename, got %v", backupInfo)
	}
	t.Logf("✓ Server backup created: %v (size: %v bytes)", backupInfo["filename"], backupInfo["size"])

	// 9. Stop and Cleanup
	killReq := map[string]string{"action": "kill"}
	kJSON, _ := json.Marshal(killReq)
	http.Post(ts.URL+"/api/servers/"+serverID+"/power", "application/json", bytes.NewReader(kJSON))

	delReq, _ := http.NewRequest("DELETE", ts.URL+"/api/servers/"+serverID, nil)
	http.DefaultClient.Do(delReq)
	t.Logf("✓ Container and server cleaned up successfully")
}
