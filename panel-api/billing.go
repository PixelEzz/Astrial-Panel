package main

import (
	"bytes"
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"net/http"
	"strings"
	"time"
)

type ProvisionRequest struct {
	Email      string            `json:"email"`
	Name       string            `json:"name"`
	EggID      string            `json:"egg_id"`
	LocationID string            `json:"location_id"`
	MemoryMB   int64             `json:"memory_mb"`
	CPULimit   int64             `json:"cpu_limit"`
	DiskMB     int64             `json:"disk_mb"`
	Env        map[string]string `json:"env"`
}

type ServerDetails struct {
	ID           string `json:"id"`
	UUID         string `json:"uuid"`
	Name         string `json:"name"`
	UserID       string `json:"user_id"`
	UserEmail    string `json:"user_email"`
	NodeID       string `json:"node_id"`
	EggID        string `json:"egg_id"`
	EggName      string `json:"egg_name"`
	Status       string `json:"status"`
	IsSuspended  bool   `json:"is_suspended"`
	MemoryMB     int64  `json:"memory_mb"`
	CPULimit     int64  `json:"cpu_limit"`
	DiskMB       int64  `json:"disk_mb"`
	Port         int    `json:"port"`
	AllocationIP string `json:"allocation_ip"`
	ContainerID  string `json:"container_id"`
	CreatedAt    string `json:"created_at"`
}

func (app *App) handleProvision(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	if r.Method != "POST" {
		http.Error(w, `{"error":"method not allowed"}`, 405)
		return
	}

	var req ProvisionRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, `{"error":"invalid json"}`, 400)
		return
	}
	if req.Email == "" || req.EggID == "" {
		http.Error(w, `{"error":"email and egg_id required"}`, 400)
		return
	}
	if req.MemoryMB <= 0 {
		req.MemoryMB = 1024
	}
	if req.CPULimit <= 0 {
		req.CPULimit = 100
	}
	if req.DiskMB <= 0 {
		req.DiskMB = 5120
	}
	if req.Name == "" {
		req.Name = fmt.Sprintf("Server-%d", time.Now().Unix()%10000)
	}

	// 1. Find or create user
	var userID string
	err := app.db.QueryRow("SELECT id FROM users WHERE email = $1", req.Email).Scan(&userID)
	if err != nil {
		userID = fmt.Sprintf("usr_%d", time.Now().UnixNano())
		defaultPW := HashPassword("client123")
		app.db.Exec("INSERT INTO users (id, email, password_hash, role) VALUES ($1, $2, $3, 'client')", userID, req.Email, defaultPW)
	}

	// 2. Smart Node Allocation (least loaded node in location)
	var nodeID, daemonURL, daemonToken, nodeFQDN string
	if req.LocationID != "" {
		err = app.db.QueryRow(`
			SELECT n.id, n.daemon_url, n.daemon_token, n.fqdn 
			FROM nodes n 
			WHERE n.location_id = $1 AND n.is_online = true 
			ORDER BY (SELECT COALESCE(SUM(s.memory_mb), 0) FROM servers s WHERE s.node_id = n.id) ASC 
			LIMIT 1`, req.LocationID).Scan(&nodeID, &daemonURL, &daemonToken, &nodeFQDN)
	}
	if nodeID == "" {
		err = app.db.QueryRow(`
			SELECT n.id, n.daemon_url, n.daemon_token, n.fqdn 
			FROM nodes n 
			WHERE n.is_online = true 
			ORDER BY (SELECT COALESCE(SUM(s.memory_mb), 0) FROM servers s WHERE s.node_id = n.id) ASC 
			LIMIT 1`).Scan(&nodeID, &daemonURL, &daemonToken, &nodeFQDN)
	}
	if nodeID == "" {
		http.Error(w, `{"error":"no available node in location"}`, 503)
		return
	}

	// 3. Allocate free port
	var allocID int
	var allocIP string
	var allocPort int
	err = app.db.QueryRow(`
		SELECT id, ip, port FROM allocations 
		WHERE node_id = $1 AND is_assigned = false 
		ORDER BY port ASC LIMIT 1`, nodeID).Scan(&allocID, &allocIP, &allocPort)
	if err != nil {
		allocIP = "127.0.0.1"
		allocPort = 25565
		// Find highest used port
		var maxPort sqlNullInt
		app.db.QueryRow("SELECT MAX(port) FROM allocations WHERE node_id = $1", nodeID).Scan(&maxPort)
		if maxPort.Valid && maxPort.Int > 0 {
			allocPort = maxPort.Int + 1
		}
		app.db.QueryRow("INSERT INTO allocations (node_id, ip, port, is_assigned) VALUES ($1, $2, $3, true) RETURNING id", nodeID, allocIP, allocPort).Scan(&allocID)
	} else {
		app.db.Exec("UPDATE allocations SET is_assigned = true WHERE id = $1", allocID)
	}

	// 4. Load Egg template
	var egg PterodactylEgg
	var imgBytes, cfgBytes, scrBytes, varBytes []byte
	err = app.db.QueryRow("SELECT id, name, category, author, description, docker_images, startup, config, scripts, variables FROM eggs WHERE id = $1", req.EggID).
		Scan(&egg.ID, &egg.Name, &egg.Category, &egg.Author, &egg.Description, &imgBytes, &egg.Startup, &cfgBytes, &scrBytes, &varBytes)
	if err != nil {
		http.Error(w, fmt.Sprintf(`{"error":"egg '%s' not found"}`, req.EggID), 404)
		return
	}
	json.Unmarshal(imgBytes, &egg.Images)
	json.Unmarshal(scrBytes, &egg.Scripts)

	// Pick default docker image
	primaryImage := "alpine:latest"
	for _, img := range egg.Images {
		if img != "" {
			primaryImage = img
			break
		}
	}

	serverID := fmt.Sprintf("srv_%d", time.Now().UnixNano())
	serverUUID := randomUUID()

	// Prepare env
	env := make(map[string]string)
	for k, v := range req.Env {
		env[k] = v
	}
	env["SERVER_MEMORY"] = fmt.Sprintf("%d", req.MemoryMB)
	env["SERVER_PORT"] = fmt.Sprintf("%d", allocPort)
	env["SERVER_IP"] = allocIP

	// 5. Call Node Daemon to create container
	daemonPayload := map[string]interface{}{
		"id":              serverID,
		"name":            req.Name,
		"image":           primaryImage,
		"env":             env,
		"startup_command": egg.Startup,
		"limits": map[string]interface{}{
			"memory_mb": req.MemoryMB,
			"cpu_limit": req.CPULimit,
			"cpuset":    "",
		},
		"ports": []map[string]interface{}{
			{
				"container_port": allocPort,
				"host_port":      allocPort,
				"protocol":       "tcp",
			},
		},
	}
	b, _ := json.Marshal(daemonPayload)
	dResp, err := http.Post(daemonURL+"/api/servers", "application/json", bytes.NewReader(b))
	var containerID string
	if err == nil {
		defer dResp.Body.Close()
		var dResult struct {
			ContainerID string `json:"container_id"`
		}
		json.NewDecoder(dResp.Body).Decode(&dResult)
		containerID = dResult.ContainerID
	}

	// 6. Run Egg Install Script if present
	if scriptMap, ok := egg.Scripts["installation"].(map[string]interface{}); ok {
		if scriptText, ok := scriptMap["script"].(string); ok && scriptText != "" {
			instPayload := map[string]interface{}{
				"image":  primaryImage,
				"script": scriptText,
				"env":    env,
			}
			instB, _ := json.Marshal(instPayload)
			http.Post(fmt.Sprintf("%s/api/servers/%s/install", daemonURL, serverID), "application/json", bytes.NewReader(instB))
		}
	}

	// 7. Insert server into DB
	_, err = app.db.Exec(`
		INSERT INTO servers (id, uuid, name, user_id, node_id, egg_id, status, is_suspended, memory_mb, cpu_limit, disk_mb, port, allocation_ip, container_id)
		VALUES ($1, $2, $3, $4, $5, $6, 'stopped', false, $7, $8, $9, $10, $11, $12)
	`, serverID, serverUUID, req.Name, userID, nodeID, req.EggID, req.MemoryMB, req.CPULimit, req.DiskMB, allocPort, allocIP, containerID)
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}

	app.db.Exec("UPDATE allocations SET server_id = $1 WHERE id = $2", serverID, allocID)

	// 8. Send Automated Welcome & Provisioned Email via Mailpit
	app.SendServerCreatedEmail(req.Email, req.Name, allocIP, allocPort)

	w.WriteHeader(201)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"status": "provisioned",
		"server": map[string]interface{}{
			"id":            serverID,
			"uuid":          serverUUID,
			"name":          req.Name,
			"egg_id":        req.EggID,
			"egg_name":      egg.Name,
			"node_id":       nodeID,
			"allocation_ip": allocIP,
			"port":          allocPort,
			"memory_mb":     req.MemoryMB,
			"cpu_limit":     req.CPULimit,
			"disk_mb":       req.DiskMB,
			"status":        "stopped",
		},
	})
}

func (app *App) handleServerSuspend(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	serverID := strings.TrimPrefix(r.URL.Path, "/api/servers/")
	serverID = strings.TrimSuffix(serverID, "/suspend")

	var userEmail, serverName, nodeURL string
	err := app.db.QueryRow(`
		SELECT u.email, s.name, n.daemon_url 
		FROM servers s 
		JOIN users u ON s.user_id = u.id 
		JOIN nodes n ON s.node_id = n.id 
		WHERE s.id = $1`, serverID).Scan(&userEmail, &serverName, &nodeURL)
	if err != nil {
		http.Error(w, `{"error":"server not found"}`, 404)
		return
	}

	// 1. Tell Daemon to stop container gracefully
	stopPayload := map[string]string{"action": "stop"}
	sb, _ := json.Marshal(stopPayload)
	http.Post(fmt.Sprintf("%s/api/servers/%s/power", nodeURL, serverID), "application/json", bytes.NewReader(sb))

	// 2. Lock startup in DB
	app.db.Exec("UPDATE servers SET is_suspended = true, status = 'suspended' WHERE id = $1", serverID)

	// 3. Send automated notification
	var body struct {
		Reason string `json:"reason"`
	}
	json.NewDecoder(r.Body).Decode(&body)
	if body.Reason == "" {
		body.Reason = "Overdue invoice on billing system"
	}
	app.SendServerSuspendedEmail(userEmail, serverName, body.Reason)

	json.NewEncoder(w).Encode(map[string]string{
		"status":    "suspended",
		"server_id": serverID,
	})
}

func (app *App) handleServerUnsuspend(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	serverID := strings.TrimPrefix(r.URL.Path, "/api/servers/")
	serverID = strings.TrimSuffix(serverID, "/unsuspend")

	var userEmail, serverName string
	err := app.db.QueryRow(`
		SELECT u.email, s.name 
		FROM servers s 
		JOIN users u ON s.user_id = u.id 
		WHERE s.id = $1`, serverID).Scan(&userEmail, &serverName)
	if err != nil {
		http.Error(w, `{"error":"server not found"}`, 404)
		return
	}

	app.db.Exec("UPDATE servers SET is_suspended = false, status = 'stopped' WHERE id = $1", serverID)
	app.SendServerUnsuspendedEmail(userEmail, serverName)

	json.NewEncoder(w).Encode(map[string]string{
		"status":    "unsuspended",
		"server_id": serverID,
	})
}

func (app *App) handleServerResize(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	serverID := strings.TrimPrefix(r.URL.Path, "/api/servers/")
	serverID = strings.TrimSuffix(serverID, "/resize")

	var nodeURL string
	err := app.db.QueryRow(`
		SELECT n.daemon_url 
		FROM servers s 
		JOIN nodes n ON s.node_id = n.id 
		WHERE s.id = $1`, serverID).Scan(&nodeURL)
	if err != nil {
		http.Error(w, `{"error":"server not found"}`, 404)
		return
	}

	var req struct {
		MemoryMB int64 `json:"memory_mb"`
		CPULimit int64 `json:"cpu_limit"`
		DiskMB   int64 `json:"disk_mb"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, `{"error":"invalid json"}`, 400)
		return
	}

	app.db.Exec("UPDATE servers SET memory_mb = $1, cpu_limit = $2, disk_mb = $3 WHERE id = $4",
		req.MemoryMB, req.CPULimit, req.DiskMB, serverID)

	// Call Daemon to resize container cgroups live
	resizePayload := map[string]interface{}{
		"memory_mb": req.MemoryMB,
		"cpu_limit": req.CPULimit,
	}
	rb, _ := json.Marshal(resizePayload)
	http.Post(fmt.Sprintf("%s/api/servers/%s/resize", nodeURL, serverID), "application/json", bytes.NewReader(rb))

	json.NewEncoder(w).Encode(map[string]interface{}{
		"status":    "resized",
		"server_id": serverID,
		"memory_mb": req.MemoryMB,
		"cpu_limit": req.CPULimit,
	})
}

func (app *App) handleServerDatabases(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	parts := strings.Split(strings.Trim(r.URL.Path, "/"), "/")
	if len(parts) < 3 {
		http.Error(w, `{"error":"invalid path"}`, 400)
		return
	}
	serverID := parts[2]

	if r.Method == "GET" {
		rows, err := app.db.Query("SELECT id, db_name, username, password, host, sso_url, created_at FROM server_databases WHERE server_id = $1", serverID)
		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		defer rows.Close()

		type DBRow struct {
			ID        string `json:"id"`
			DBName    string `json:"db_name"`
			Username  string `json:"username"`
			Password  string `json:"password"`
			Host      string `json:"host"`
			SSOUrl    string `json:"sso_url"`
			CreatedAt string `json:"created_at"`
		}
		var list []DBRow
		for rows.Next() {
			var d DBRow
			var cat time.Time
			rows.Scan(&d.ID, &d.DBName, &d.Username, &d.Password, &d.Host, &d.SSOUrl, &cat)
			d.CreatedAt = cat.Format(time.RFC3339)
			list = append(list, d)
		}
		json.NewEncoder(w).Encode(list)
		return
	}

	if r.Method == "POST" {
		var req struct {
			DBName string `json:"db_name"`
		}
		json.NewDecoder(r.Body).Decode(&req)
		if req.DBName == "" {
			req.DBName = fmt.Sprintf("s%s_db", serverID[:6])
		}
		dbID := fmt.Sprintf("db_%d", time.Now().UnixNano())
		user := fmt.Sprintf("u_%s", serverID[:6])
		pass := randomHex(12)
		host := "127.0.0.1:3306"
		sso := fmt.Sprintf("http://localhost:8080/pma?user=%s", user)

		_, err := app.db.Exec(`
			INSERT INTO server_databases (id, server_id, db_name, username, password, host, sso_url)
			VALUES ($1, $2, $3, $4, $5, $6, $7)
		`, dbID, serverID, req.DBName, user, pass, host, sso)
		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}

		w.WriteHeader(201)
		json.NewEncoder(w).Encode(map[string]interface{}{
			"id":        dbID,
			"db_name":   req.DBName,
			"username":  user,
			"password":  pass,
			"host":      host,
			"sso_url":   sso,
		})
		return
	}
}

type sqlNullInt struct {
	Int   int
	Valid bool
}

func (s *sqlNullInt) Scan(value interface{}) error {
	if value == nil {
		s.Int, s.Valid = 0, false
		return nil
	}
	if v, ok := value.(int64); ok {
		s.Int, s.Valid = int(v), true
		return nil
	}
	return nil
}

func randomHex(n int) string {
	b := make([]byte, n)
	rand.Read(b)
	return hex.EncodeToString(b)
}

func randomUUID() string {
	b := make([]byte, 16)
	rand.Read(b)
	b[6] = (b[6] & 0x0f) | 0x40
	b[8] = (b[8] & 0x3f) | 0x80
	return fmt.Sprintf("%x-%x-%x-%x-%x", b[0:4], b[4:6], b[6:8], b[8:10], b[10:])
}
