package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"
)

type PterodactylEgg struct {
	Meta        EggMeta                `json:"meta"`
	ID          string                 `json:"id,omitempty"`
	Name        string                 `json:"name"`
	Category    string                 `json:"category"`
	Author      string                 `json:"author"`
	Description string                 `json:"description"`
	Features    []string               `json:"features"`
	Images      map[string]string      `json:"docker_images"`
	Startup     string                 `json:"startup"`
	Config      map[string]interface{} `json:"config"`
	Scripts     map[string]interface{} `json:"scripts"`
	Variables   []EggVariable          `json:"variables"`
}

type EggMeta struct {
	Version   string  `json:"version"`
	UpdateURL *string `json:"update_url,omitempty"`
}

type EggVariable struct {
	Name         string `json:"name"`
	Description  string `json:"description"`
	EnvVariable  string `json:"env_variable"`
	DefaultValue string `json:"default_value"`
	UserViewable bool   `json:"user_viewable"`
	UserEditable bool   `json:"user_editable"`
	Rules        string `json:"rules"`
}

func (d *DB) SeedEggLibrary(eggsDir string) error {
	return filepath.Walk(eggsDir, func(path string, info os.FileInfo, err error) error {
		if err != nil || info.IsDir() || !strings.HasSuffix(path, ".json") {
			return nil
		}
		data, err := os.ReadFile(path)
		if err != nil {
			return nil
		}
		var egg PterodactylEgg
		if err := json.Unmarshal(data, &egg); err != nil {
			return nil
		}

		baseName := strings.TrimSuffix(filepath.Base(path), ".json")
		category := filepath.Base(filepath.Dir(path))
		if egg.Category == "" {
			egg.Category = category
		}

		imagesJSON, _ := json.Marshal(egg.Images)
		configJSON, _ := json.Marshal(egg.Config)
		scriptsJSON, _ := json.Marshal(egg.Scripts)
		varsJSON, _ := json.Marshal(egg.Variables)

		_, err = d.Exec(`
			INSERT INTO eggs (id, name, category, author, description, docker_images, startup, config, scripts, variables)
			VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
			ON CONFLICT (id) DO UPDATE SET
				name = EXCLUDED.name,
				category = EXCLUDED.category,
				docker_images = EXCLUDED.docker_images,
				startup = EXCLUDED.startup,
				config = EXCLUDED.config,
				scripts = EXCLUDED.scripts,
				variables = EXCLUDED.variables
		`, baseName, egg.Name, egg.Category, egg.Author, egg.Description, imagesJSON, egg.Startup, configJSON, scriptsJSON, varsJSON)

		if err != nil {
			log.Printf("Failed to seed egg %s: %v", baseName, err)
		}
		return nil
	})
}

func (app *App) handleEggs(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	if r.Method == "GET" {
		rows, err := app.db.Query("SELECT id, name, category, author, description, docker_images, startup, config, scripts, variables FROM eggs ORDER BY category, name")
		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		defer rows.Close()

		var eggs []PterodactylEgg
		for rows.Next() {
			var e PterodactylEgg
			var imgBytes, cfgBytes, scrBytes, varBytes []byte
			rows.Scan(&e.ID, &e.Name, &e.Category, &e.Author, &e.Description, &imgBytes, &e.Startup, &cfgBytes, &scrBytes, &varBytes)
			json.Unmarshal(imgBytes, &e.Images)
			json.Unmarshal(cfgBytes, &e.Config)
			json.Unmarshal(scrBytes, &e.Scripts)
			json.Unmarshal(varBytes, &e.Variables)
			e.Meta = EggMeta{Version: "PTDL_v2"}
			eggs = append(eggs, e)
		}
		json.NewEncoder(w).Encode(eggs)
		return
	}

	if r.Method == "POST" {
		// Import Egg
		var egg PterodactylEgg
		if err := json.NewDecoder(r.Body).Decode(&egg); err != nil {
			http.Error(w, `{"error":"invalid egg json"}`, 400)
			return
		}
		if egg.ID == "" {
			egg.ID = strings.ToLower(strings.ReplaceAll(egg.Name, " ", "_"))
			if egg.ID == "" {
				egg.ID = fmt.Sprintf("egg_%d", time.Now().UnixNano())
			}
		}
		if egg.Category == "" {
			egg.Category = "custom"
		}

		imagesJSON, _ := json.Marshal(egg.Images)
		configJSON, _ := json.Marshal(egg.Config)
		scriptsJSON, _ := json.Marshal(egg.Scripts)
		varsJSON, _ := json.Marshal(egg.Variables)

		_, err := app.db.Exec(`
			INSERT INTO eggs (id, name, category, author, description, docker_images, startup, config, scripts, variables)
			VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
			ON CONFLICT (id) DO UPDATE SET
				name = EXCLUDED.name,
				category = EXCLUDED.category,
				docker_images = EXCLUDED.docker_images,
				startup = EXCLUDED.startup,
				config = EXCLUDED.config,
				scripts = EXCLUDED.scripts,
				variables = EXCLUDED.variables
		`, egg.ID, egg.Name, egg.Category, egg.Author, egg.Description, imagesJSON, egg.Startup, configJSON, scriptsJSON, varsJSON)

		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		w.WriteHeader(201)
		json.NewEncoder(w).Encode(egg)
		return
	}
}

func (app *App) handleEggExport(w http.ResponseWriter, r *http.Request) {
	eggID := strings.TrimPrefix(r.URL.Path, "/api/admin/eggs/")
	eggID = strings.TrimSuffix(eggID, "/export")

	var e PterodactylEgg
	var imgBytes, cfgBytes, scrBytes, varBytes []byte
	err := app.db.QueryRow("SELECT id, name, category, author, description, docker_images, startup, config, scripts, variables FROM eggs WHERE id = $1", eggID).
		Scan(&e.ID, &e.Name, &e.Category, &e.Author, &e.Description, &imgBytes, &e.Startup, &cfgBytes, &scrBytes, &varBytes)
	if err != nil {
		http.Error(w, `{"error":"egg not found"}`, 404)
		return
	}
	json.Unmarshal(imgBytes, &e.Images)
	json.Unmarshal(cfgBytes, &e.Config)
	json.Unmarshal(scrBytes, &e.Scripts)
	json.Unmarshal(varBytes, &e.Variables)
	e.Meta = EggMeta{Version: "PTDL_v2"}

	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Content-Disposition", fmt.Sprintf("attachment; filename=\"egg-%s.json\"", e.ID))
	json.NewEncoder(w).Encode(e)
}
