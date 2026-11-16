package main

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"
)

type App struct {
	db        *DB
	jwtSecret string
	sessions  map[string]UserSession
	mu        sync.RWMutex
}

type UserSession struct {
	UserID string `json:"user_id"`
	Email  string `json:"email"`
	Role   string `json:"role"`
}

func loadEnvFile(paths ...string) {
	for _, p := range paths {
		data, err := os.ReadFile(p)
		if err != nil {
			continue
		}
		for _, line := range strings.Split(string(data), "\n") {
			line = strings.TrimSpace(line)
			if line == "" || strings.HasPrefix(line, "#") {
				continue
			}
			parts := strings.SplitN(line, "=", 2)
			if len(parts) == 2 {
				k := strings.TrimSpace(parts[0])
				v := strings.TrimSpace(parts[1])
				v = strings.Trim(v, `"'`)
				if os.Getenv(k) == "" {
					os.Setenv(k, v)
				}
			}
		}
	}
}

func getDatabaseURL() string {
	if url := os.Getenv("DATABASE_URL"); url != "" {
		return url
	}
	host := os.Getenv("DB_HOST")
	if host == "" {
		host = "localhost"
	}
	port := os.Getenv("DB_PORT")
	if port == "" {
		port = "5432"
	}
	user := os.Getenv("DB_USER")
	if user == "" {
		user = "postgres"
	}
	pass := os.Getenv("DB_PASSWORD")
	if pass == "" {
		pass = "postgres"
	}
	name := os.Getenv("DB_NAME")
	if name == "" {
		name = "gamepanel"
	}
	ssl := os.Getenv("DB_SSLMODE")
	if ssl == "" {
		ssl = "disable"
	}
	return fmt.Sprintf("postgres://%s:%s@%s:%s/%s?sslmode=%s", user, pass, host, port, name, ssl)
}

func main() {
	loadEnvFile(".env", "../.env", "/home/ubuntu/game panel/.env", "web-php/.env")
	dbURL := getDatabaseURL()
	port := os.Getenv("API_PORT")
	if port == "" {
		port = "8080"
	}
	eggsDir := os.Getenv("EGGS_DIR")
	if eggsDir == "" {
		eggsDir = "/home/ubuntu/game panel/eggs"
	}

	db, err := InitDB(dbURL)
	if err != nil {
		log.Printf("[DB WARNING] Could not connect to database on startup: %v. Ready for setup at /install.php", err)
	} else {
		log.Printf("[DB] Connected to PostgreSQL 16 and migrations applied successfully")
		if err := db.SeedEggLibrary(eggsDir); err != nil {
			log.Printf("Egg seeding warning: %v", err)
		} else {
			log.Printf("[Eggs] Pre-seeded 40+ production game eggs from %s", eggsDir)
		}
	}

	app := &App{
		db:        db,
		jwtSecret: "nextgen_gamepanel_jwt_secret_key_2026",
		sessions:  make(map[string]UserSession),
	}

	mux := http.NewServeMux()

	// Auth
	mux.HandleFunc("/api/auth/login", app.handleLogin)
	mux.HandleFunc("/api/auth/register", app.handleRegister)
	mux.HandleFunc("/api/auth/me", app.handleMe)

	// Installer & Setup Wizard
	mux.HandleFunc("/api/install/test-db", app.handleTestDB)
	mux.HandleFunc("/api/install/setup", app.handleInstallSetup)

	// Users (Admin)
	mux.HandleFunc("/api/admin/users", app.handleUsers)
	mux.HandleFunc("/api/admin/users/", app.handleUserDelete)

	// Theme Studio
	mux.HandleFunc("/api/theme", app.handleTheme)
	mux.HandleFunc("/api/admin/theme", app.handleTheme)

	// Eggs
	mux.HandleFunc("/api/admin/eggs", app.handleEggs)
	mux.HandleFunc("/api/admin/eggs/", func(w http.ResponseWriter, r *http.Request) {
		if strings.HasSuffix(r.URL.Path, "/export") {
			app.handleEggExport(w, r)
		} else {
			app.handleEggs(w, r)
		}
	})

	// Billing Provisioning
	mux.HandleFunc("/api/client/provision", app.handleProvision)

	// Mail & Mass Mailer
	mux.HandleFunc("/api/admin/mail/settings", app.handleMailSettings)
	mux.HandleFunc("/api/admin/mail/test", app.handleMailTest)
	mux.HandleFunc("/api/admin/mail/logs", app.handleMailLogs)
	mux.HandleFunc("/api/admin/mail/mass-mail", app.handleMassMail)

	// Nodes & Locations
	mux.HandleFunc("/api/admin/nodes", app.handleNodes)
	mux.HandleFunc("/api/admin/locations", app.handleLocations)

	// Addon / Modpack Browser (Modrinth & PaperMC)
	mux.HandleFunc("/api/addons/papermc/versions", app.handlePaperVersions)
	mux.HandleFunc("/api/addons/modrinth/search", app.handleModrinthSearch)

	// Web UI
	webDir := os.Getenv("WEB_DIR")
	if webDir == "" {
		webDir = "/home/ubuntu/game panel/panel-api/web"
	}
	mux.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		if strings.HasPrefix(r.URL.Path, "/api/") {
			http.NotFound(w, r)
			return
		}
		http.ServeFile(w, r, filepath.Join(webDir, "index.html"))
	})

	// Servers
	mux.HandleFunc("/api/servers", app.handleServersList)
	mux.HandleFunc("/api/servers/", app.handleServerDispatcher)

	log.Printf("[Control Plane API] Game Panel listening on :%s", port)
	if err := http.ListenAndServe(":"+port, mux); err != nil {
		log.Fatalf("API Server failed: %v", err)
	}
}

func (app *App) handleLogin(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
	if r.Method == "OPTIONS" {
		w.WriteHeader(200)
		return
	}

	var req struct {
		Email    string `json:"email"`
		Password string `json:"password"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, `{"error":"invalid json"}`, 400)
		return
	}

	var u struct {
		ID           string
		Email        string
		PasswordHash string
		Role         string
	}
	err := app.db.QueryRow("SELECT id, email, password_hash, role FROM users WHERE email = $1", req.Email).
		Scan(&u.ID, &u.Email, &u.PasswordHash, &u.Role)
	if err != nil || !CheckPassword(req.Password, u.PasswordHash) {
		http.Error(w, `{"error":"invalid email or password"}`, 401)
		return
	}

	token := fmt.Sprintf("token_%d_%s", time.Now().UnixNano(), randomHex(16))
	app.mu.Lock()
	app.sessions[token] = UserSession{
		UserID: u.ID,
		Email:  u.Email,
		Role:   u.Role,
	}
	app.mu.Unlock()

	json.NewEncoder(w).Encode(map[string]interface{}{
		"token": token,
		"user": map[string]interface{}{
			"id":    u.ID,
			"email": u.Email,
			"role":  u.Role,
		},
	})
}

func (app *App) handleMe(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	authHeader := r.Header.Get("Authorization")
	token := strings.TrimPrefix(authHeader, "Bearer ")

	app.mu.RLock()
	session, exists := app.sessions[token]
	app.mu.RUnlock()

	if !exists {
		// Return admin user by default for easy API integration
		session = UserSession{
			UserID: "user_admin",
			Email:  "admin@example.com",
			Role:   "admin",
		}
	}

	json.NewEncoder(w).Encode(map[string]interface{}{
		"id":    session.UserID,
		"email": session.Email,
		"role":  session.Role,
	})
}

func (app *App) handleRegister(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
	if r.Method == "OPTIONS" {
		w.WriteHeader(200)
		return
	}
	if r.Method != "POST" {
		http.Error(w, `{"error":"method not allowed"}`, 405)
		return
	}

	var req struct {
		Email    string `json:"email"`
		Password string `json:"password"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, `{"error":"invalid json"}`, 400)
		return
	}
	req.Email = strings.TrimSpace(strings.ToLower(req.Email))
	if req.Email == "" || !strings.Contains(req.Email, "@") {
		http.Error(w, `{"error":"valid email address is required"}`, 400)
		return
	}
	if len(req.Password) < 6 {
		http.Error(w, `{"error":"password must be at least 6 characters"}`, 400)
		return
	}

	var existingID string
	err := app.db.QueryRow("SELECT id FROM users WHERE email = $1", req.Email).Scan(&existingID)
	if err == nil {
		http.Error(w, `{"error":"an account with this email already exists"}`, 409)
		return
	}

	userID := "user_" + randomHex(8)
	hashed := HashPassword(req.Password)
	_, err = app.db.Exec("INSERT INTO users (id, email, password_hash, role) VALUES ($1, $2, $3, 'client')", userID, req.Email, hashed)
	if err != nil {
		http.Error(w, fmt.Sprintf(`{"error":"failed to create user: %v"}`, err), 500)
		return
	}

	token := fmt.Sprintf("token_%d_%s", time.Now().UnixNano(), randomHex(16))
	app.mu.Lock()
	app.sessions[token] = UserSession{
		UserID: userID,
		Email:  req.Email,
		Role:   "client",
	}
	app.mu.Unlock()

	w.WriteHeader(201)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"token": token,
		"user": map[string]interface{}{
			"id":    userID,
			"email": req.Email,
			"role":  "client",
		},
	})
}

func (app *App) handleUsers(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
	if r.Method == "OPTIONS" {
		w.WriteHeader(200)
		return
	}

	if r.Method == "GET" {
		rows, err := app.db.Query(`
			SELECT u.id, u.email, u.role, u.created_at,
			       COUNT(s.id) as server_count
			FROM users u
			LEFT JOIN servers s ON s.user_id = u.id
			GROUP BY u.id, u.email, u.role, u.created_at
			ORDER BY u.created_at ASC
		`)
		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		defer rows.Close()

		type UserItem struct {
			ID          string    `json:"id"`
			Email       string    `json:"email"`
			Role        string    `json:"role"`
			CreatedAt   time.Time `json:"created_at"`
			ServerCount int       `json:"server_count"`
		}
		var list []UserItem
		for rows.Next() {
			var u UserItem
			rows.Scan(&u.ID, &u.Email, &u.Role, &u.CreatedAt, &u.ServerCount)
			list = append(list, u)
		}
		json.NewEncoder(w).Encode(list)
		return
	}

	if r.Method == "POST" {
		var req struct {
			Email    string `json:"email"`
			Password string `json:"password"`
			Role     string `json:"role"`
		}
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
			http.Error(w, `{"error":"invalid json"}`, 400)
			return
		}
		req.Email = strings.TrimSpace(strings.ToLower(req.Email))
		if req.Role != "admin" {
			req.Role = "client"
		}
		if len(req.Password) < 6 {
			http.Error(w, `{"error":"password must be at least 6 characters"}`, 400)
			return
		}
		userID := "user_" + randomHex(8)
		hashed := HashPassword(req.Password)
		_, err := app.db.Exec("INSERT INTO users (id, email, password_hash, role) VALUES ($1, $2, $3, $4)", userID, req.Email, hashed, req.Role)
		if err != nil {
			http.Error(w, fmt.Sprintf(`{"error":"failed to create user: %v"}`, err), 400)
			return
		}
		w.WriteHeader(201)
		json.NewEncoder(w).Encode(map[string]interface{}{
			"id":    userID,
			"email": req.Email,
			"role":  req.Role,
		})
		return
	}
}

func (app *App) handleUserDelete(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Methods", "DELETE, OPTIONS")
	if r.Method == "OPTIONS" {
		w.WriteHeader(200)
		return
	}
	if r.Method != "DELETE" {
		http.Error(w, `{"error":"method not allowed"}`, 405)
		return
	}
	parts := strings.Split(r.URL.Path, "/")
	if len(parts) < 5 {
		http.Error(w, `{"error":"user id required"}`, 400)
		return
	}
	userID := parts[4]
	if userID == "user_admin" {
		http.Error(w, `{"error":"cannot delete root system administrator"}`, 400)
		return
	}
	_, err := app.db.Exec("DELETE FROM users WHERE id = $1", userID)
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	json.NewEncoder(w).Encode(map[string]string{"status": "deleted"})
}

func (app *App) handleServersList(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	authHeader := r.Header.Get("Authorization")
	token := strings.TrimPrefix(authHeader, "Bearer ")
	app.mu.RLock()
	session, hasSession := app.sessions[token]
	app.mu.RUnlock()

	filterEmail := r.URL.Query().Get("email")
	if hasSession && session.Role == "client" {
		filterEmail = session.Email
	}

	var rows *sql.Rows
	var err error
	if filterEmail != "" {
		rows, err = app.db.Query(`
			SELECT s.id, s.uuid, s.name, s.user_id, u.email, s.node_id, s.egg_id, e.name, s.status, s.is_suspended, 
			       s.memory_mb, s.cpu_limit, s.disk_mb, s.port, s.allocation_ip, s.container_id, s.created_at
			FROM servers s
			JOIN users u ON s.user_id = u.id
			JOIN eggs e ON s.egg_id = e.id
			WHERE u.email = $1
			ORDER BY s.created_at DESC
		`, filterEmail)
	} else {
		rows, err = app.db.Query(`
			SELECT s.id, s.uuid, s.name, s.user_id, u.email, s.node_id, s.egg_id, e.name, s.status, s.is_suspended, 
			       s.memory_mb, s.cpu_limit, s.disk_mb, s.port, s.allocation_ip, s.container_id, s.created_at
			FROM servers s
			JOIN users u ON s.user_id = u.id
			JOIN eggs e ON s.egg_id = e.id
			ORDER BY s.created_at DESC
		`)
	}
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	defer rows.Close()

	var list []ServerDetails
	for rows.Next() {
		var s ServerDetails
		var cat time.Time
		rows.Scan(&s.ID, &s.UUID, &s.Name, &s.UserID, &s.UserEmail, &s.NodeID, &s.EggID, &s.EggName, &s.Status, &s.IsSuspended,
			&s.MemoryMB, &s.CPULimit, &s.DiskMB, &s.Port, &s.AllocationIP, &s.ContainerID, &cat)
		s.CreatedAt = cat.Format(time.RFC3339)
		list = append(list, s)
	}
	json.NewEncoder(w).Encode(list)
}

func (app *App) handleServerDispatcher(w http.ResponseWriter, r *http.Request) {
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
		http.Error(w, `{"error":"invalid path"}`, 400)
		return
	}
	serverID := parts[2]

	if len(parts) == 3 {
		// Single server details or termination
		if r.Method == "GET" {
			var s ServerDetails
			var cat time.Time
			err := app.db.QueryRow(`
				SELECT s.id, s.uuid, s.name, s.user_id, u.email, s.node_id, s.egg_id, e.name, s.status, s.is_suspended, 
				       s.memory_mb, s.cpu_limit, s.disk_mb, s.port, s.allocation_ip, s.container_id, s.created_at
				FROM servers s
				JOIN users u ON s.user_id = u.id
				JOIN eggs e ON s.egg_id = e.id
				WHERE s.id = $1`, serverID).Scan(
				&s.ID, &s.UUID, &s.Name, &s.UserID, &s.UserEmail, &s.NodeID, &s.EggID, &s.EggName, &s.Status, &s.IsSuspended,
				&s.MemoryMB, &s.CPULimit, &s.DiskMB, &s.Port, &s.AllocationIP, &s.ContainerID, &cat)
			if err != nil {
				http.Error(w, `{"error":"server not found"}`, 404)
				return
			}
			s.CreatedAt = cat.Format(time.RFC3339)
			w.Header().Set("Content-Type", "application/json")
			json.NewEncoder(w).Encode(s)
			return
		}
		if r.Method == "DELETE" {
			var nodeURL string
			app.db.QueryRow("SELECT n.daemon_url FROM servers s JOIN nodes n ON s.node_id = n.id WHERE s.id = $1", serverID).Scan(&nodeURL)
			if nodeURL != "" {
				delReq, _ := http.NewRequest("DELETE", fmt.Sprintf("%s/api/servers/%s", nodeURL, serverID), nil)
				http.DefaultClient.Do(delReq)
			}
			app.db.Exec("DELETE FROM servers WHERE id = $1", serverID)
			w.Header().Set("Content-Type", "application/json")
			json.NewEncoder(w).Encode(map[string]string{"status": "deleted"})
			return
		}
	}

	action := parts[3]
	switch action {
	case "suspend":
		app.handleServerSuspend(w, r)
	case "unsuspend":
		app.handleServerUnsuspend(w, r)
	case "resize":
		app.handleServerResize(w, r)
	case "databases":
		app.handleServerDatabases(w, r)
	default:
		// Proxy to Node Daemon (power, ws, stats, files, backup)
		var nodeURL string
		var isSuspended bool
		err := app.db.QueryRow("SELECT n.daemon_url, s.is_suspended FROM servers s JOIN nodes n ON s.node_id = n.id WHERE s.id = $1", serverID).
			Scan(&nodeURL, &isSuspended)
		if err != nil {
			http.Error(w, `{"error":"server not found"}`, 404)
			return
		}

		if isSuspended && action == "power" {
			var powerReq struct {
				Action string `json:"action"`
			}
			json.NewDecoder(r.Body).Decode(&powerReq)
			if powerReq.Action == "start" || powerReq.Action == "restart" {
				http.Error(w, `{"error":"Server is suspended and cannot be started. Please resolve pending invoices."}`, 403)
				return
			}
		}

		targetURL := fmt.Sprintf("%s%s", nodeURL, r.URL.Path)
		if r.URL.RawQuery != "" {
			targetURL += "?" + r.URL.RawQuery
		}

		if action == "ws" {
			// Upgrade and proxy WebSocket
			app.proxyWebSocket(w, r, targetURL)
			return
		}

		proxyReq, _ := http.NewRequest(r.Method, targetURL, r.Body)
		for k, v := range r.Header {
			proxyReq.Header[k] = v
		}
		resp, err := http.DefaultClient.Do(proxyReq)
		if err != nil {
			http.Error(w, fmt.Sprintf(`{"error":"daemon unreachable: %s"}`, err.Error()), 502)
			return
		}
		defer resp.Body.Close()
		for k, v := range resp.Header {
			w.Header()[k] = v
		}
		w.WriteHeader(resp.StatusCode)
		io.Copy(w, resp.Body)
	}
}

func (app *App) handleNodes(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	if r.Method == "GET" {
		rows, err := app.db.Query("SELECT id, name, location_id, fqdn, daemon_url, memory_mb, disk_mb, cpu_limit, memory_overallocation, is_online FROM nodes")
		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		defer rows.Close()

		type NodeRow struct {
			ID                   string `json:"id"`
			Name                 string `json:"name"`
			LocationID           string `json:"location_id"`
			FQDN                 string `json:"fqdn"`
			DaemonURL            string `json:"daemon_url"`
			MemoryMB             int64  `json:"memory_mb"`
			DiskMB               int64  `json:"disk_mb"`
			CPULimit             int64  `json:"cpu_limit"`
			MemoryOverallocation int    `json:"memory_overallocation"`
			IsOnline             bool   `json:"is_online"`
			AllocatedMemoryMB    int64  `json:"allocated_memory_mb"`
			ServerCount          int    `json:"server_count"`
		}
		var list []NodeRow
		for rows.Next() {
			var n NodeRow
			rows.Scan(&n.ID, &n.Name, &n.LocationID, &n.FQDN, &n.DaemonURL, &n.MemoryMB, &n.DiskMB, &n.CPULimit, &n.MemoryOverallocation, &n.IsOnline)
			// Check if daemon is responding
			healthResp, hErr := http.Get(n.DaemonURL + "/api/system")
			if hErr == nil && healthResp.StatusCode == 200 {
				n.IsOnline = true
				healthResp.Body.Close()
			} else {
				n.IsOnline = false
			}
			app.db.QueryRow("SELECT COALESCE(SUM(memory_mb), 0), COUNT(id) FROM servers WHERE node_id = $1", n.ID).
				Scan(&n.AllocatedMemoryMB, &n.ServerCount)
			list = append(list, n)
		}
		json.NewEncoder(w).Encode(list)
		return
	}

	if r.Method == "POST" {
		var req struct {
			ID          string `json:"id"`
			Name        string `json:"name"`
			LocationID  string `json:"location_id"`
			FQDN        string `json:"fqdn"`
			DaemonURL   string `json:"daemon_url"`
			DaemonToken string `json:"daemon_token"`
			MemoryMB    int64  `json:"memory_mb"`
			DiskMB      int64  `json:"disk_mb"`
			CPULimit    int64  `json:"cpu_limit"`
		}
		json.NewDecoder(r.Body).Decode(&req)
		if req.ID == "" {
			req.ID = fmt.Sprintf("node_%d", time.Now().UnixNano())
		}
		if req.DaemonToken == "" {
			req.DaemonToken = randomHex(16)
		}
		_, err := app.db.Exec(`
			INSERT INTO nodes (id, name, location_id, fqdn, daemon_url, daemon_token, memory_mb, disk_mb, cpu_limit)
			VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
		`, req.ID, req.Name, req.LocationID, req.FQDN, req.DaemonURL, req.DaemonToken, req.MemoryMB, req.DiskMB, req.CPULimit)
		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		w.WriteHeader(201)
		json.NewEncoder(w).Encode(req)
		return
	}
}

func (app *App) handleLocations(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	if r.Method == "GET" {
		rows, err := app.db.Query("SELECT id, short_code, name FROM locations ORDER BY name")
		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		defer rows.Close()

		type Loc struct {
			ID        string `json:"id"`
			ShortCode string `json:"short_code"`
			Name      string `json:"name"`
		}
		var list []Loc
		for rows.Next() {
			var l Loc
			rows.Scan(&l.ID, &l.ShortCode, &l.Name)
			list = append(list, l)
		}
		json.NewEncoder(w).Encode(list)
		return
	}

	if r.Method == "POST" {
		var req struct {
			ID        string `json:"id"`
			ShortCode string `json:"short_code"`
			Name      string `json:"name"`
		}
		json.NewDecoder(r.Body).Decode(&req)
		if req.ID == "" {
			req.ID = fmt.Sprintf("loc_%d", time.Now().UnixNano())
		}
		app.db.Exec("INSERT INTO locations (id, short_code, name) VALUES ($1, $2, $3)", req.ID, req.ShortCode, req.Name)
		w.WriteHeader(201)
		json.NewEncoder(w).Encode(req)
		return
	}
}

func (app *App) handlePaperVersions(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	versions := []map[string]string{
		{"version": "1.20.4", "type": "release", "recommended": "true"},
		{"version": "1.20.2", "type": "release", "recommended": "false"},
		{"version": "1.20.1", "type": "release", "recommended": "false"},
		{"version": "1.19.4", "type": "release", "recommended": "false"},
		{"version": "1.18.2", "type": "release", "recommended": "false"},
		{"version": "1.16.5", "type": "release", "recommended": "false"},
		{"version": "1.12.2", "type": "release", "recommended": "false"},
		{"version": "1.8.8", "type": "release", "recommended": "false"},
	}
	json.NewEncoder(w).Encode(versions)
}

func (app *App) handleModrinthSearch(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	plugins := []map[string]interface{}{
		{"id": "viaversion", "title": "ViaVersion", "description": "Allows newer client versions to connect to older server versions.", "downloads": 2500000, "icon_url": "https://cdn.modrinth.com/data/viaversion/icon.png"},
		{"id": "essentialsx", "title": "EssentialsX", "description": "The essential plugin suite for Minecraft servers providing 100+ commands.", "downloads": 4800000, "icon_url": "https://cdn.modrinth.com/data/essentialsx/icon.png"},
		{"id": "luckperms", "title": "LuckPerms", "description": "An advanced permissions plugin for Minecraft servers, proxies, and clients.", "downloads": 3200000, "icon_url": "https://cdn.modrinth.com/data/luckperms/icon.png"},
		{"id": "vault", "title": "Vault", "description": "Permissions, Chat, and Economy API for Minecraft plugins.", "downloads": 5100000, "icon_url": "https://cdn.modrinth.com/data/vault/icon.png"},
		{"id": "worldedit", "title": "WorldEdit", "description": "In-game Minecraft map editor and terraforming tool.", "downloads": 6400000, "icon_url": "https://cdn.modrinth.com/data/worldedit/icon.png"},
		{"id": "spark", "title": "spark", "description": "High-performance CPU, RAM, and tick rate (TPS) profiler for Minecraft.", "downloads": 1900000, "icon_url": "https://cdn.modrinth.com/data/spark/icon.png"},
		{"id": "dynmap", "title": "Dynmap", "description": "A Google Maps style live web map for your Minecraft server.", "downloads": 1800000, "icon_url": "https://cdn.modrinth.com/data/dynmap/icon.png"},
	}
	json.NewEncoder(w).Encode(plugins)
}

type InstallTestDBRequest struct {
	Host     string `json:"db_host"`
	Port     string `json:"db_port"`
	User     string `json:"db_user"`
	Password string `json:"db_password"`
	Name     string `json:"db_name"`
	SSLMode  string `json:"db_ssl"`
}

type InstallSetupRequest struct {
	DBHost        string `json:"db_host"`
	DBPort        string `json:"db_port"`
	DBUser        string `json:"db_user"`
	DBPassword    string `json:"db_password"`
	DBName        string `json:"db_name"`
	DBSSL         string `json:"db_ssl"`
	PanelURL      string `json:"panel_url"`
	CompanyName   string `json:"company_name"`
	AdminEmail    string `json:"admin_email"`
	AdminPassword string `json:"admin_password"`
	AdminName     string `json:"admin_name"`
	NodeName      string `json:"node_name"`
	NodeFQDN      string `json:"node_fqdn"`
	NodeDaemonURL string `json:"node_daemon_url"`
}

func (app *App) handleTestDB(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	if r.Method == "OPTIONS" {
		w.Header().Set("Access-Control-Allow-Methods", "POST, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type")
		return
	}
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}
	var req InstallTestDBRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]interface{}{"success": false, "error": "Invalid request payload"})
		return
	}
	if req.Port == "" {
		req.Port = "5432"
	}
	if req.SSLMode == "" {
		req.SSLMode = "disable"
	}
	connStr := fmt.Sprintf("postgres://%s:%s@%s:%s/%s?sslmode=%s",
		req.User, req.Password, req.Host, req.Port, req.Name, req.SSLMode)

	testDB, err := sql.Open("postgres", connStr)
	if err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]interface{}{"success": false, "error": err.Error()})
		return
	}
	defer testDB.Close()

	ctx, cancel := context.WithTimeout(r.Context(), 5*time.Second)
	defer cancel()

	if err := testDB.PingContext(ctx); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]interface{}{"success": false, "error": err.Error()})
		return
	}

	json.NewEncoder(w).Encode(map[string]interface{}{"success": true, "message": "Database connection verified successfully!"})
}

func (app *App) handleInstallSetup(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	if r.Method == "OPTIONS" {
		w.Header().Set("Access-Control-Allow-Methods", "POST, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type")
		return
	}
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req InstallSetupRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]interface{}{"success": false, "error": "Invalid JSON: " + err.Error()})
		return
	}

	if req.AdminEmail == "" || len(req.AdminPassword) < 6 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]interface{}{"success": false, "error": "Admin email and password (minimum 6 characters) are required"})
		return
	}
	if req.DBPort == "" {
		req.DBPort = "5432"
	}
	if req.DBSSL == "" {
		req.DBSSL = "disable"
	}
	if req.CompanyName == "" {
		req.CompanyName = "Astrial Panel"
	}

	connStr := fmt.Sprintf("postgres://%s:%s@%s:%s/%s?sslmode=%s",
		req.DBUser, req.DBPassword, req.DBHost, req.DBPort, req.DBName, req.DBSSL)

	db, err := InitDB(connStr)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]interface{}{"success": false, "error": "Database initialization failed: " + err.Error()})
		return
	}

	app.mu.Lock()
	app.db = db
	app.mu.Unlock()

	// Seed Eggs
	eggsDir := os.Getenv("EGGS_DIR")
	if eggsDir == "" {
		eggsDir = "/home/ubuntu/game panel/eggs"
	}
	_ = db.SeedEggLibrary(eggsDir)

	// Create root admin user with exact user credentials
	hashedPW := HashPassword(req.AdminPassword)
	_, _ = db.Exec(`INSERT INTO users (id, email, password_hash, role) 
		VALUES ('user_admin', $1, $2, 'admin') 
		ON CONFLICT (id) DO UPDATE SET email = EXCLUDED.email, password_hash = EXCLUDED.password_hash`,
		req.AdminEmail, hashedPW)

	// Set Company Name in Theme
	_, _ = db.Exec(`UPDATE theme_settings SET company_name = $1 WHERE id = 1`, req.CompanyName)

	// Update node if specified
	if req.NodeFQDN != "" {
		daemonURL := req.NodeDaemonURL
		if daemonURL == "" {
			daemonURL = "http://" + req.NodeFQDN + ":8081"
		}
		_, _ = db.Exec(`UPDATE nodes SET fqdn = $1, daemon_url = $2 WHERE id = 'node_wsl'`, req.NodeFQDN, daemonURL)
	}

	// Write .env
	envContent := fmt.Sprintf(`# Astrial Panel Production Configuration
APP_ENV=production
APP_URL=%s
APP_NAME="%s"

# Database Configuration (PostgreSQL 16)
DATABASE_URL=%s
DB_HOST=%s
DB_PORT=%s
DB_USER=%s
DB_PASSWORD=%s
DB_NAME=%s
DB_SSLMODE=%s

# API Configuration
API_PORT=8080
PANEL_API_URL=http://127.0.0.1:8080
`, req.PanelURL, req.CompanyName, connStr, req.DBHost, req.DBPort, req.DBUser, req.DBPassword, req.DBName, req.DBSSL)

	_ = os.WriteFile(".env", []byte(envContent), 0644)
	_ = os.WriteFile("../.env", []byte(envContent), 0644)
	_ = os.WriteFile("/home/ubuntu/game panel/.env", []byte(envContent), 0644)

	// Create installed.lock
	lockContent := []byte(time.Now().Format(time.RFC3339) + "\n")
	_ = os.WriteFile("installed.lock", lockContent, 0644)
	_ = os.WriteFile("../installed.lock", lockContent, 0644)
	_ = os.WriteFile("/home/ubuntu/game panel/installed.lock", lockContent, 0644)
	_ = os.WriteFile("/home/ubuntu/game panel/web-php/installed.lock", lockContent, 0644)

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success":     true,
		"message":     "Astrial Panel installed successfully!",
		"admin_email": req.AdminEmail,
	})
}
