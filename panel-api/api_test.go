package main

import (
	"bytes"
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"os"
	"strings"
	"testing"
	"time"
)

func TestBillingLifecycleAndMailDelivery(t *testing.T) {
	// 1. Clear Mailpit
	delReq, _ := http.NewRequest("DELETE", "http://localhost:8025/api/v1/messages", nil)
	http.DefaultClient.Do(delReq)

	// 2. Init App
	dbURL := os.Getenv("DATABASE_URL")
	if dbURL == "" {
		dbURL = "postgres://postgres:postgres@localhost:5432/gamepanel?sslmode=disable"
	}
	db, err := InitDB(dbURL)
	if err != nil {
		t.Fatalf("failed to init db: %v", err)
	}

	db.SeedEggLibrary("/home/ubuntu/game panel/eggs")
	app := &App{
		db:        db,
		jwtSecret: "test_secret",
		sessions:  make(map[string]UserSession),
	}

	mux := http.NewServeMux()
	mux.HandleFunc("/api/client/provision", app.handleProvision)
	mux.HandleFunc("/api/servers", app.handleServersList)
	mux.HandleFunc("/api/servers/", app.handleServerDispatcher)

	ts := httptest.NewServer(mux)
	defer ts.Close()

	// 3. Test Provision Server
	provReq := ProvisionRequest{
		Email:      "host_customer@example.com",
		Name:       "Production Paper Server",
		EggID:      "paper",
		LocationID: "loc_wsl",
		MemoryMB:   2048,
		CPULimit:   200,
		DiskMB:     10240,
	}
	reqBytes, _ := json.Marshal(provReq)
	pResp, err := http.Post(ts.URL+"/api/client/provision", "application/json", bytes.NewReader(reqBytes))
	if err != nil {
		t.Fatalf("provision request failed: %v", err)
	}
	defer pResp.Body.Close()

	if pResp.StatusCode != 201 {
		b, _ := io.ReadAll(pResp.Body)
		t.Fatalf("provision failed with code %d: %s", pResp.StatusCode, string(b))
	}

	var pData struct {
		Status string `json:"status"`
		Server struct {
			ID   string `json:"id"`
			Port int    `json:"port"`
		} `json:"server"`
	}
	json.NewDecoder(pResp.Body).Decode(&pData)
	serverID := pData.Server.ID
	if serverID == "" {
		t.Fatalf("expected serverID, got empty")
	}
	t.Logf("✓ Server provisioned successfully: ID: %s, Port: %d", serverID, pData.Server.Port)

	// 4. Verify Welcome/Created email arrived in Mailpit API
	time.Sleep(1 * time.Second) // Wait for async mail sender
	mResp, err := http.Get("http://localhost:8025/api/v1/messages")
	if err != nil {
		t.Fatalf("failed to query Mailpit API: %v", err)
	}
	defer mResp.Body.Close()

	var mailData struct {
		Total    int `json:"total"`
		Messages []struct {
			ID      string `json:"ID"`
			Subject string `json:"Subject"`
			HTML    string `json:"HTML"`
		} `json:"messages"`
	}
	json.NewDecoder(mResp.Body).Decode(&mailData)
	if mailData.Total < 1 {
		t.Fatalf("expected at least 1 email in Mailpit, got %d", mailData.Total)
	}
	firstMsg := mailData.Messages[0]
	if !strings.Contains(firstMsg.Subject, "Server Ready") {
		t.Fatalf("expected Subject to contain 'Server Ready', got %s", firstMsg.Subject)
	}
	if !strings.Contains(firstMsg.HTML, "SFTP Port") {
		t.Fatalf("expected email HTML body to contain SFTP info")
	}
	t.Logf("✓ Verified automated 'Server Created' HTML email in Mailpit: %s", firstMsg.Subject)

	// 5. Test Suspend Server (Overdue invoice flow)
	suspendReq := map[string]string{"reason": "Overdue invoice #INV-9021"}
	sBytes, _ := json.Marshal(suspendReq)
	sResp, err := http.Post(ts.URL+"/api/servers/"+serverID+"/suspend", "application/json", bytes.NewReader(sBytes))
	if err != nil {
		t.Fatalf("suspend failed: %v", err)
	}
	sResp.Body.Close()
	t.Logf("✓ Server %s suspended successfully", serverID)

	time.Sleep(1 * time.Second)
	// Check suspension email in Mailpit
	mResp2, _ := http.Get("http://localhost:8025/api/v1/messages")
	json.NewDecoder(mResp2.Body).Decode(&mailData)
	mResp2.Body.Close()

	foundSuspendedMail := false
	for _, m := range mailData.Messages {
		if strings.Contains(m.Subject, "Server Suspended") {
			foundSuspendedMail = true
			break
		}
	}
	if !foundSuspendedMail {
		t.Fatalf("did not find 'Server Suspended' email in Mailpit")
	}
	t.Logf("✓ Verified automated 'Server Suspended' email in Mailpit!")

	// 6. Test Live Resize (Plan upgrade/downgrade)
	resizeReq := map[string]interface{}{
		"memory_mb": 4096,
		"cpu_limit": 400,
		"disk_mb":   20480,
	}
	rBytes, _ := json.Marshal(resizeReq)
	rResp, err := http.Post(ts.URL+"/api/servers/"+serverID+"/resize", "application/json", bytes.NewReader(rBytes))
	if err != nil {
		t.Fatalf("resize failed: %v", err)
	}
	rResp.Body.Close()
	t.Logf("✓ Server %s resized live to 4096MB / 400%% CPU", serverID)

	// 7. Test Unsuspend Server (Invoice paid flow)
	unsuspendResp, err := http.Post(ts.URL+"/api/servers/"+serverID+"/unsuspend", "application/json", nil)
	if err != nil {
		t.Fatalf("unsuspend failed: %v", err)
	}
	unsuspendResp.Body.Close()
	t.Logf("✓ Server %s unsuspended successfully", serverID)

	time.Sleep(1 * time.Second)
	// Check unsuspension email in Mailpit
	mResp3, _ := http.Get("http://localhost:8025/api/v1/messages")
	json.NewDecoder(mResp3.Body).Decode(&mailData)
	mResp3.Body.Close()

	foundUnsuspendedMail := false
	for _, m := range mailData.Messages {
		if strings.Contains(m.Subject, "Server Unsuspended") {
			foundUnsuspendedMail = true
			break
		}
	}
	if !foundUnsuspendedMail {
		t.Fatalf("did not find 'Server Unsuspended' email in Mailpit")
	}
	t.Logf("✓ Verified automated 'Server Unsuspended' email in Mailpit!")

	// 8. Terminate Server (Delete flow)
	delServerReq, _ := http.NewRequest("DELETE", ts.URL+"/api/servers/"+serverID, nil)
	delResp, err := http.DefaultClient.Do(delServerReq)
	if err != nil || delResp.StatusCode != 200 {
		t.Fatalf("server deletion failed: %v", err)
	}
	delResp.Body.Close()
	t.Logf("✓ Server %s terminated and cleaned up", serverID)
}
