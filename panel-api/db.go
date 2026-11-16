package main

import (
	"crypto/hmac"
	"crypto/rand"
	"crypto/sha256"
	"database/sql"
	"encoding/hex"
	"fmt"
	"log"
	"strings"
	"time"

	_ "github.com/lib/pq"
)

type DB struct {
	*sql.DB
}

func InitDB(connStr string) (*DB, error) {
	db, err := sql.Open("postgres", connStr)
	if err != nil {
		return nil, err
	}
	db.SetMaxOpenConns(25)
	db.SetMaxIdleConns(5)
	db.SetConnMaxLifetime(5 * time.Minute)

	if err := db.Ping(); err != nil {
		return nil, err
	}

	schema := `
	CREATE TABLE IF NOT EXISTS users (
		id VARCHAR(64) PRIMARY KEY,
		email VARCHAR(255) UNIQUE NOT NULL,
		password_hash VARCHAR(255) NOT NULL,
		role VARCHAR(32) NOT NULL DEFAULT 'client',
		totp_secret VARCHAR(64) DEFAULT '',
		created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS locations (
		id VARCHAR(64) PRIMARY KEY,
		short_code VARCHAR(32) UNIQUE NOT NULL,
		name VARCHAR(255) NOT NULL,
		created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS nodes (
		id VARCHAR(64) PRIMARY KEY,
		name VARCHAR(255) NOT NULL,
		location_id VARCHAR(64) REFERENCES locations(id) ON DELETE CASCADE,
		fqdn VARCHAR(255) NOT NULL,
		daemon_url VARCHAR(255) NOT NULL,
		daemon_token VARCHAR(255) NOT NULL,
		memory_mb BIGINT NOT NULL DEFAULT 8192,
		disk_mb BIGINT NOT NULL DEFAULT 51200,
		cpu_limit BIGINT NOT NULL DEFAULT 400,
		memory_overallocation INT NOT NULL DEFAULT 100,
		disk_overallocation INT NOT NULL DEFAULT 100,
		is_online BOOLEAN NOT NULL DEFAULT true,
		created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS eggs (
		id VARCHAR(64) PRIMARY KEY,
		name VARCHAR(255) NOT NULL,
		category VARCHAR(64) NOT NULL,
		author VARCHAR(255) DEFAULT '',
		description TEXT DEFAULT '',
		docker_images JSONB DEFAULT '{}'::jsonb,
		startup TEXT NOT NULL,
		config JSONB DEFAULT '{}'::jsonb,
		scripts JSONB DEFAULT '{}'::jsonb,
		variables JSONB DEFAULT '[]'::jsonb,
		created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS servers (
		id VARCHAR(64) PRIMARY KEY,
		uuid VARCHAR(64) UNIQUE NOT NULL,
		name VARCHAR(255) NOT NULL,
		user_id VARCHAR(64) REFERENCES users(id) ON DELETE CASCADE,
		node_id VARCHAR(64) REFERENCES nodes(id) ON DELETE CASCADE,
		egg_id VARCHAR(64) REFERENCES eggs(id) ON DELETE CASCADE,
		status VARCHAR(32) NOT NULL DEFAULT 'stopped',
		is_suspended BOOLEAN NOT NULL DEFAULT false,
		memory_mb BIGINT NOT NULL DEFAULT 1024,
		cpu_limit BIGINT NOT NULL DEFAULT 100,
		disk_mb BIGINT NOT NULL DEFAULT 5120,
		port INT NOT NULL,
		allocation_ip VARCHAR(64) NOT NULL DEFAULT '127.0.0.1',
		container_id VARCHAR(128) DEFAULT '',
		created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS allocations (
		id SERIAL PRIMARY KEY,
		node_id VARCHAR(64) REFERENCES nodes(id) ON DELETE CASCADE,
		ip VARCHAR(64) NOT NULL,
		port INT NOT NULL,
		server_id VARCHAR(64) REFERENCES servers(id) ON DELETE SET NULL,
		is_assigned BOOLEAN NOT NULL DEFAULT false,
		UNIQUE(node_id, ip, port)
	);

	CREATE TABLE IF NOT EXISTS server_databases (
		id VARCHAR(64) PRIMARY KEY,
		server_id VARCHAR(64) REFERENCES servers(id) ON DELETE CASCADE,
		db_name VARCHAR(128) NOT NULL,
		username VARCHAR(128) NOT NULL,
		password VARCHAR(128) NOT NULL,
		host VARCHAR(128) NOT NULL,
		sso_url VARCHAR(255) DEFAULT '',
		created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS theme_settings (
		id INT PRIMARY KEY DEFAULT 1,
		company_name VARCHAR(128) DEFAULT 'Astrial Panel',
		support_url TEXT DEFAULT '',
		footer_text TEXT DEFAULT '© 2026 GamePanel Technologies. All rights reserved.',
		header_scripts TEXT DEFAULT '',
		primary_color VARCHAR(32) DEFAULT '#6366f1',
		secondary_color VARCHAR(32) DEFAULT '#3b82f6',
		accent_color VARCHAR(32) DEFAULT '#10b981',
		mode VARCHAR(16) DEFAULT 'dark',
		blur INT DEFAULT 12,
		wallpaper_url TEXT DEFAULT '',
		logo_url TEXT DEFAULT '',
		favicon_url TEXT DEFAULT '',
		footer_links JSONB DEFAULT '[]'::jsonb,
		custom_css TEXT DEFAULT '',
		updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);

	ALTER TABLE theme_settings ADD COLUMN IF NOT EXISTS company_name VARCHAR(128) DEFAULT 'Astrial Panel';
	ALTER TABLE theme_settings ADD COLUMN IF NOT EXISTS support_url TEXT DEFAULT '';
	ALTER TABLE theme_settings ADD COLUMN IF NOT EXISTS footer_text TEXT DEFAULT '© 2026 GamePanel Technologies. All rights reserved.';
	ALTER TABLE theme_settings ADD COLUMN IF NOT EXISTS header_scripts TEXT DEFAULT '';

	CREATE TABLE IF NOT EXISTS mail_settings (
		id INT PRIMARY KEY DEFAULT 1,
		host VARCHAR(128) DEFAULT 'localhost',
		port INT DEFAULT 1025,
		username VARCHAR(128) DEFAULT '',
		password VARCHAR(128) DEFAULT '',
		encryption VARCHAR(16) DEFAULT 'none',
		from_address VARCHAR(128) DEFAULT 'noreply@gamepanel.local',
		from_name VARCHAR(128) DEFAULT 'Game Panel',
		updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS mail_logs (
		id SERIAL PRIMARY KEY,
		to_address VARCHAR(255) NOT NULL,
		subject VARCHAR(255) NOT NULL,
		status VARCHAR(32) NOT NULL,
		error_msg TEXT DEFAULT '',
		sent_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS api_keys (
		id VARCHAR(64) PRIMARY KEY,
		user_id VARCHAR(64) REFERENCES users(id) ON DELETE CASCADE,
		name VARCHAR(255) NOT NULL,
		token VARCHAR(255) UNIQUE NOT NULL,
		allowed_ips TEXT DEFAULT '',
		scopes TEXT DEFAULT 'admin',
		created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
	);
	`

	if _, err := db.Exec(schema); err != nil {
		return nil, fmt.Errorf("failed to run migrations: %w", err)
	}

	d := &DB{db}
	if err := d.seedDefaults(); err != nil {
		log.Printf("Warning: seed defaults returned: %v", err)
	}
	return d, nil
}

func (d *DB) seedDefaults() error {
	// Seed Admin
	adminPW := HashPassword("admin123")
	d.Exec(`INSERT INTO users (id, email, password_hash, role) 
		VALUES ('user_admin', 'admin@example.com', $1, 'admin') 
		ON CONFLICT (email) DO NOTHING`, adminPW)

	// Seed Client
	clientPW := HashPassword("client123")
	d.Exec(`INSERT INTO users (id, email, password_hash, role) 
		VALUES ('user_client', 'client@example.com', $1, 'client') 
		ON CONFLICT (email) DO NOTHING`, clientPW)

	// Seed Location
	d.Exec(`INSERT INTO locations (id, short_code, name) 
		VALUES ('loc_wsl', 'WSL-1', 'WSL Local Development') 
		ON CONFLICT (short_code) DO NOTHING`)

	// Seed Node
	d.Exec(`INSERT INTO nodes (id, name, location_id, fqdn, daemon_url, daemon_token, memory_mb, disk_mb, cpu_limit, memory_overallocation) 
		VALUES ('node_wsl', 'WSL Node 1', 'loc_wsl', 'localhost', 'http://localhost:8081', 'daemon_secret_token', 16384, 102400, 1200, 150) 
		ON CONFLICT (id) DO NOTHING`)

	// Seed Ports
	for p := 25565; p <= 25575; p++ {
		d.Exec(`INSERT INTO allocations (node_id, ip, port, is_assigned) 
			VALUES ('node_wsl', '127.0.0.1', $1, false) 
			ON CONFLICT (node_id, ip, port) DO NOTHING`, p)
	}

	// Seed Theme
	d.Exec(`INSERT INTO theme_settings (id, primary_color, secondary_color, accent_color, mode, blur, custom_css)
		VALUES (1, '#6366f1', '#3b82f6', '#10b981', 'dark', 12, '/* Custom CSS */')
		ON CONFLICT (id) DO NOTHING`)

	// Seed Mail
	d.Exec(`INSERT INTO mail_settings (id, host, port, username, password, encryption, from_address, from_name)
		VALUES (1, 'localhost', 1025, '', '', 'none', 'noreply@gamepanel.local', 'Game Panel Cloud')
		ON CONFLICT (id) DO NOTHING`)

	// Seed API Key for Billing Integrations (WHMCS, Paymenter, Stripe)
	d.Exec(`INSERT INTO api_keys (id, user_id, name, token, scopes)
		VALUES ('key_billing', 'user_admin', 'WHMCS Billing Integration', 'billing_secret_key_123', 'admin,provision')
		ON CONFLICT (id) DO NOTHING`)

	return nil
}

func HashPassword(pw string) string {
	salt := make([]byte, 16)
	rand.Read(salt)
	h := hmac.New(sha256.New, salt)
	h.Write([]byte(pw))
	return hex.EncodeToString(salt) + ":" + hex.EncodeToString(h.Sum(nil))
}

func CheckPassword(pw, hash string) bool {
	parts := strings.Split(hash, ":")
	if len(parts) != 2 {
		return false
	}
	salt, err := hex.DecodeString(parts[0])
	if err != nil {
		return false
	}
	expected, err := hex.DecodeString(parts[1])
	if err != nil {
		return false
	}
	h := hmac.New(sha256.New, salt)
	h.Write([]byte(pw))
	return hmac.Equal(h.Sum(nil), expected)
}
