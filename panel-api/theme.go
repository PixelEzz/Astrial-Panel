package main

import (
	"encoding/json"
	"net/http"
)

type ThemeSettings struct {
	CompanyName    string        `json:"company_name"`
	SupportURL     string        `json:"support_url"`
	FooterText     string        `json:"footer_text"`
	HeaderScripts  string        `json:"header_scripts"`
	PrimaryColor   string        `json:"primary_color"`
	SecondaryColor string        `json:"secondary_color"`
	AccentColor    string        `json:"accent_color"`
	Mode           string        `json:"mode"` // dark, light, oled
	Blur           int           `json:"blur"`
	WallpaperURL   string        `json:"wallpaper_url"`
	LogoURL        string        `json:"logo_url"`
	FaviconURL     string        `json:"favicon_url"`
	FooterLinks    []FooterLink  `json:"footer_links"`
	CustomCSS      string        `json:"custom_css"`
}

type FooterLink struct {
	Title string `json:"title"`
	URL   string `json:"url"`
}

func (app *App) handleTheme(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
	if r.Method == "OPTIONS" {
		w.WriteHeader(200)
		return
	}

	if r.Method == "GET" {
		var t ThemeSettings
		var footerBytes []byte
		err := app.db.QueryRow(`
			SELECT COALESCE(company_name, 'Astrial Panel'),
			       COALESCE(support_url, ''),
			       COALESCE(footer_text, '© 2026 GamePanel Technologies. All rights reserved.'),
			       COALESCE(header_scripts, ''),
			       primary_color, secondary_color, accent_color, mode, blur, 
			       COALESCE(wallpaper_url, ''), COALESCE(logo_url, ''), COALESCE(favicon_url, ''), 
			       footer_links, COALESCE(custom_css, '') 
			FROM theme_settings WHERE id = 1
		`).Scan(&t.CompanyName, &t.SupportURL, &t.FooterText, &t.HeaderScripts,
			&t.PrimaryColor, &t.SecondaryColor, &t.AccentColor, &t.Mode, &t.Blur, 
			&t.WallpaperURL, &t.LogoURL, &t.FaviconURL, &footerBytes, &t.CustomCSS)
		if err != nil {
			t = ThemeSettings{
				CompanyName:    "GamePanel Cloud",
				SupportURL:     "",
				FooterText:     "© 2026 GamePanel Technologies. All rights reserved.",
				HeaderScripts:  "",
				PrimaryColor:   "#6366f1",
				SecondaryColor: "#3b82f6",
				AccentColor:    "#10b981",
				Mode:           "dark",
				Blur:           12,
			}
		} else {
			json.Unmarshal(footerBytes, &t.FooterLinks)
		}
		json.NewEncoder(w).Encode(t)
		return
	}

	if r.Method == "POST" {
		var t ThemeSettings
		if err := json.NewDecoder(r.Body).Decode(&t); err != nil {
			http.Error(w, `{"error":"invalid theme json"}`, 400)
			return
		}
		if t.CompanyName == "" {
			t.CompanyName = "GamePanel Cloud"
		}
		if t.FooterText == "" {
			t.FooterText = "© 2026 GamePanel Technologies. All rights reserved."
		}
		footerBytes, _ := json.Marshal(t.FooterLinks)
		_, err := app.db.Exec(`
			UPDATE theme_settings SET
				company_name = $1,
				support_url = $2,
				footer_text = $3,
				header_scripts = $4,
				primary_color = $5,
				secondary_color = $6,
				accent_color = $7,
				mode = $8,
				blur = $9,
				wallpaper_url = $10,
				logo_url = $11,
				favicon_url = $12,
				footer_links = $13,
				custom_css = $14,
				updated_at = NOW()
			WHERE id = 1
		`, t.CompanyName, t.SupportURL, t.FooterText, t.HeaderScripts,
			t.PrimaryColor, t.SecondaryColor, t.AccentColor, t.Mode, t.Blur, 
			t.WallpaperURL, t.LogoURL, t.FaviconURL, footerBytes, t.CustomCSS)
		if err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		json.NewEncoder(w).Encode(t)
		return
	}
}
