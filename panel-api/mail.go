package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"net/smtp"
	"time"
)

type MailSettings struct {
	Host        string `json:"host"`
	Port        int    `json:"port"`
	Username    string `json:"username"`
	Password    string `json:"password"`
	Encryption  string `json:"encryption"`
	FromAddress string `json:"from_address"`
	FromName    string `json:"from_name"`
}

type MailLog struct {
	ID        int       `json:"id"`
	ToAddress string    `json:"to_address"`
	Subject   string    `json:"subject"`
	Status    string    `json:"status"`
	ErrorMsg  string    `json:"error_msg"`
	SentAt    time.Time `json:"sent_at"`
}

func (app *App) SendEmail(to, subject, htmlBody string) error {
	var s MailSettings
	err := app.db.QueryRow("SELECT host, port, username, password, encryption, from_address, from_name FROM mail_settings WHERE id = 1").
		Scan(&s.Host, &s.Port, &s.Username, &s.Password, &s.Encryption, &s.FromAddress, &s.FromName)
	if err != nil {
		s = MailSettings{
			Host:        "localhost",
			Port:        1025,
			FromAddress: "noreply@gamepanel.local",
			FromName:    "Game Panel Cloud",
		}
	}

	addr := fmt.Sprintf("%s:%d", s.Host, s.Port)
	fromHeader := fmt.Sprintf("%s <%s>", s.FromName, s.FromAddress)

	msg := fmt.Sprintf("From: %s\r\nTo: %s\r\nSubject: %s\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n%s",
		fromHeader, to, subject, htmlBody)

	var auth smtp.Auth
	if s.Username != "" && s.Password != "" {
		auth = smtp.PlainAuth("", s.Username, s.Password, s.Host)
	}

	sendErr := smtp.SendMail(addr, auth, s.FromAddress, []string{to}, []byte(msg))
	status := "delivered"
	errMsg := ""
	if sendErr != nil {
		status = "failed"
		errMsg = sendErr.Error()
		log.Printf("[Mailer] Failed to send email to %s: %v", to, sendErr)
	} else {
		log.Printf("[Mailer] Delivered email to %s: '%s'", to, subject)
	}

	app.db.Exec("INSERT INTO mail_logs (to_address, subject, status, error_msg) VALUES ($1, $2, $3, $4)",
		to, subject, status, errMsg)

	return sendErr
}

// Lifecycle Emails
func (app *App) SendServerCreatedEmail(to, serverName, ip string, port int) {
	html := fmt.Sprintf(`
		<div style="font-family:sans-serif;background:#0f172a;color:#f8fafc;padding:30px;border-radius:8px;">
			<h2 style="color:#6366f1;">🎮 Your Server is Ready!</h2>
			<p>Congratulations, your server <b>%s</b> has been provisioned successfully.</p>
			<table style="width:100%%;border-collapse:collapse;margin:20px 0;background:#1e293b;border-radius:6px;">
				<tr><td style="padding:10px;border-bottom:1px solid #334155;">Server IP:</td><td style="padding:10px;border-bottom:1px solid #334155;font-family:monospace;color:#38bdf8;">%s:%d</td></tr>
				<tr><td style="padding:10px;border-bottom:1px solid #334155;">SFTP Host:</td><td style="padding:10px;border-bottom:1px solid #334155;font-family:monospace;">%s</td></tr>
				<tr><td style="padding:10px;">SFTP Port:</td><td style="padding:10px;font-family:monospace;">2022</td></tr>
			</table>
			<p>You can manage your server, console, files, and backups directly from your client control panel.</p>
			<hr style="border:none;border-top:1px solid #334155;margin:20px 0;"/>
			<small style="color:#94a3b8;">Sent automatically by Game Panel Billing Automation</small>
		</div>
	`, serverName, ip, port, ip)
	go app.SendEmail(to, fmt.Sprintf("🎮 Server Ready: %s", serverName), html)
}

func (app *App) SendServerSuspendedEmail(to, serverName, reason string) {
	html := fmt.Sprintf(`
		<div style="font-family:sans-serif;background:#0f172a;color:#f8fafc;padding:30px;border-radius:8px;">
			<h2 style="color:#ef4444;">⚠️ Server Suspended</h2>
			<p>Your server <b>%s</b> has been suspended.</p>
			<p><b>Reason:</b> %s</p>
			<p>Your server data and files are safely preserved. Once the overdue invoice is resolved, your server will automatically unsuspend.</p>
		</div>
	`, serverName, reason)
	go app.SendEmail(to, fmt.Sprintf("⚠️ Server Suspended: %s", serverName), html)
}

func (app *App) SendServerUnsuspendedEmail(to, serverName string) {
	html := fmt.Sprintf(`
		<div style="font-family:sans-serif;background:#0f172a;color:#f8fafc;padding:30px;border-radius:8px;">
			<h2 style="color:#10b981;">✅ Server Restored</h2>
			<p>Your server <b>%s</b> has been unsuspended and is ready for use.</p>
		</div>
	`, serverName)
	go app.SendEmail(to, fmt.Sprintf("✅ Server Unsuspended: %s", serverName), html)
}

func (app *App) handleMailSettings(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	if r.Method == "GET" {
		var s MailSettings
		app.db.QueryRow("SELECT host, port, username, password, encryption, from_address, from_name FROM mail_settings WHERE id = 1").
			Scan(&s.Host, &s.Port, &s.Username, &s.Password, &s.Encryption, &s.FromAddress, &s.FromName)
		json.NewEncoder(w).Encode(s)
		return
	}

	if r.Method == "POST" {
		var s MailSettings
		json.NewDecoder(r.Body).Decode(&s)
		app.db.Exec(`UPDATE mail_settings SET host = $1, port = $2, username = $3, password = $4, encryption = $5, from_address = $6, from_name = $7, updated_at = NOW() WHERE id = 1`,
			s.Host, s.Port, s.Username, s.Password, s.Encryption, s.FromAddress, s.FromName)
		json.NewEncoder(w).Encode(map[string]string{"status": "saved"})
		return
	}
}

func (app *App) handleMailTest(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	var req struct {
		To string `json:"to"`
	}
	json.NewDecoder(r.Body).Decode(&req)
	if req.To == "" {
		req.To = "admin@example.com"
	}
	err := app.SendEmail(req.To, "Game Panel SMTP Connection Test", "<h3>SMTP Connection Successful!</h3><p>Your transactional email engine is working properly.</p>")
	if err != nil {
		http.Error(w, fmt.Sprintf(`{"error":"%s"}`, err.Error()), 500)
		return
	}
	json.NewEncoder(w).Encode(map[string]string{"status": "sent", "to": req.To})
}

func (app *App) handleMailLogs(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	rows, err := app.db.Query("SELECT id, to_address, subject, status, error_msg, sent_at FROM mail_logs ORDER BY id DESC LIMIT 50")
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	defer rows.Close()

	var logs []MailLog
	for rows.Next() {
		var l MailLog
		rows.Scan(&l.ID, &l.ToAddress, &l.Subject, &l.Status, &l.ErrorMsg, &l.SentAt)
		logs = append(logs, l)
	}
	json.NewEncoder(w).Encode(logs)
}

func (app *App) handleMassMail(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	var req struct {
		TargetType string `json:"target_type"` // all, node, location, egg
		TargetID   string `json:"target_id"`
		Subject    string `json:"subject"`
		BodyHTML   string `json:"body_html"`
	}
	json.NewDecoder(r.Body).Decode(&req)

	query := "SELECT DISTINCT u.email FROM users u"
	var args []interface{}

	switch req.TargetType {
	case "node":
		query = "SELECT DISTINCT u.email FROM users u JOIN servers s ON s.user_id = u.id WHERE s.node_id = $1"
		args = append(args, req.TargetID)
	case "location":
		query = "SELECT DISTINCT u.email FROM users u JOIN servers s ON s.user_id = u.id JOIN nodes n ON s.node_id = n.id WHERE n.location_id = $1"
		args = append(args, req.TargetID)
	case "egg":
		query = "SELECT DISTINCT u.email FROM users u JOIN servers s ON s.user_id = u.id WHERE s.egg_id = $1"
		args = append(args, req.TargetID)
	}

	rows, err := app.db.Query(query, args...)
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	defer rows.Close()

	count := 0
	for rows.Next() {
		var email string
		rows.Scan(&email)
		app.SendEmail(email, req.Subject, req.BodyHTML)
		count++
	}

	json.NewEncoder(w).Encode(map[string]interface{}{
		"status":     "sent",
		"recipients": count,
	})
}
