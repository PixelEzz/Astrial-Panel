# Astrial Panel

> **Commercial-Grade, Next-Generation Game Server Hosting Panel & Node Daemon**  
> Engineered in high-concurrency Go, modern PHP 8, PostgreSQL 16, and Redis 7 as a powerful, ultra-responsive, white-label commercial alternative to Pterodactyl and Pelican.

---

## Architecture Overview

```
                                      +---------------------------------------------+
                                      |         Nginx + PHP 8 Web Portal            |
                                      |     (Astrial Client & Admin Management)     |
                                      +----------------------+----------------------+
                                                             |
                                                 REST API / Bearer Token
                                                             v
+------------------------+             +---------------------------------------------+
|    Billing Engines     |             |           Astrial Control Plane             |
| (WHMCS, Stripe, Wise)  +------------>|               (Go API Service)              |
+------------------------+             +----------+-------------------+--------------+
                                                  |                   |
                                    mTLS / JWT    |                   |  PostgreSQL 16
                                    REST & WS     |                   |  Redis 7 Cache
                                                  v                   v
                                       +-------------------+  +----------------------+
                                       |   Astrial Wings   |  |   Database & Redis   |
                                       |  (Go Node Daemon) |  |   Persistent Storage |
                                       +---------+---------+  +----------------------+
                                                 |
                                     Docker API Socket
                                                 v
                                    +------------------------+
                                    | Game Server Containers |
                                    | (Minecraft, Rust, etc) |
                                    +------------------------+
```

Astrial Panel splits workload management into three decoupled layers:
1. **Control Plane API (`panel-api`):** Compiled Go binary handling authentication, multi-tenant RBAC, egg templating, node allocation, billing webhooks, database provisioning, and transactional mailing.
2. **Node Daemon (`daemon`):** High-performance Go daemon interfacing directly with `/var/run/docker.sock` over authenticated REST and WebSocket protocols for low-latency console streaming, interactive TTY, power lifecycle (`start`, `restart`, `stop`, `kill`), and live cgroup telemetry.
3. **Web Management Portal (`web-php`):** Nginx + PHP 8 frontend with zero external framework overhead, featuring authentic Pterodactyl-grade server management, full 9-subtab server workspace, live terminal streaming, Monaco config editing, and zero-rebuild white-label theming.

---

## Core Capabilities

### 1. 100% Pterodactyl Egg Compatibility & 44+ Game Library
- Native **Pterodactyl & Pelican `.json` Egg Importer & Exporter**.
- Pre-seeded with ready-to-deploy templates across all game tiers:
  - **Minecraft Ecosystem:** Paper, Purpur, Folia, Velocity Proxy, BungeeCord, Fabric, Forge, NeoForge, Bedrock Dedicated Server.
  - **SteamCMD & Survival:** Rust (Vanilla + Oxide/uMod), ARK: Survival Evolved & Ascended, Palworld, Valheim, Enshrouded, 7 Days to Die, Project Zomboid, V Rising, Conan Exiles, Factorio, Satisfactory, Unturned, Terraria (TShock).
  - **Competitive FPS:** Counter-Strike 2 (CS2), Garry's Mod, Team Fortress 2, Left 4 Dead 2, Arma 3, Squad, Insurgency: Sandstorm.
  - **Roleplay Frameworks:** FiveM, alt:V, RAGE Multiplayer, Multi Theft Auto (MTA:SA).
  - **Standalone Runtimes:** Node.js, Python, Go, Java, Lavalink, standalone PostgreSQL/MariaDB/Redis containers, generic Alpine/Ubuntu sandboxes.
- Curated official Steam CDN game artwork banners for all templates and servers.

### 2. Pterodactyl-Grade 9-Subtab Server Management
- **Console:** RFC 6455 low-latency WebSocket TTY stream with interactive command execution, live terminal logs, and real-time CPU, RAM, Disk, and Network telemetry.
- **File Manager:** Directory navigation, file uploads, syntax-highlighted code editor, and live file updates.
- **Databases:** Automated per-server MySQL/MariaDB database provisioning with secure password reveals and SSO links.
- **Schedules:** Cron-based automated schedules for recurring tasks, backups, and server restarts.
- **Subusers & Permissions:** Granular staff access controls without granting root administrative credentials.
- **Backups:** Full daemon-level tarball snapshots with lock and restore actions.
- **Network:** Port allocation dispatcher with primary port indicators.
- **Startup:** Docker image selector, startup command overrides, and interactive egg environment variable configuration.
- **Settings:** SFTP connection details and secure server re-installation.

### 3. Hosting Reseller White-Label Studio (Zero-Code Customization)
- Live administrative branding editor:
  - Company name, brand logo, and favicon URLs.
  - 4K login page wallpapers with curated one-click high-resolution presets (Cyberpunk, Nebula, Datacenter, OLED Stealth).
  - Theme engine supporting **Pure OLED Black (`#000000`)**, Dark Slate, and Light modes.
  - Primary, secondary, and accent color palette pickers with 6 reseller presets.
  - Live CSS injection and analytics tracking script insertion with zero recompilation or container restarts.

### 4. Billing Automation & Provider Webhooks
- REST endpoints and webhooks compatible with **WHMCS, Paymenter, Wise, and Stripe**:
  - `POST /api/client/provision`: Automated instant provisioning with least-loaded node allocation.
  - `POST /api/servers/{id}/suspend`: Graceful container stop and startup lock on overdue invoices.
  - `POST /api/servers/{id}/unsuspend`: Immediate restoration upon payment clearance.
  - Zero-downtime plan resizing via live cgroups v2 limits.
  - Integrated transactional SMTP delivery for automated notifications.

---

## Directory Structure

```
├── daemon/                 # Wings-replacement Go node daemon (Docker socket & WebSocket TTY)
├── panel-api/              # Control Plane Go API (Fiber/PostgreSQL/Redis/Billing/Theme)
├── web-php/                # PHP 8 + Nginx frontend portal (Zero-rebuild UI & White-Label)
│   ├── views/
│   │   ├── header.php      # Dynamic head, CSS variables, and branding
│   │   └── sidebar.php     # Role-aware navigation (Client vs Admin)
│   ├── login.php           # Commercial authentication portal
│   ├── index.php           # Main server console, cluster HUD, and admin suites
│   └── helpers.php         # Internal API client utilities
├── eggs/                   # 44+ pre-seeded Pterodactyl/Pelican game templates
├── deploy/                 # Dockerfiles, Nginx configurations, and deployment scripts
├── tests/                  # Comprehensive Playwright E2E and integration test suite
└── docker-compose.dev.yml  # Local test infrastructure (PostgreSQL 16, Redis 7, Mailpit)
```

---

## Quickstart & Local Setup

### Prerequisites
- Docker & Docker Compose
- Go 1.22+
- PHP 8.0+ & Nginx

### 1. Launch Infrastructure Services
```bash
docker compose -f docker-compose.dev.yml up -d
```
This spins up:
- PostgreSQL 16 on `:5432`
- Redis 7 on `:6379`
- Mailpit (SMTP `:1025`, Web UI `:8025`)

### 2. Compile & Run the Control Plane API
```bash
cd panel-api
go build -o /usr/local/bin/astrial-api .
astrial-api
```
The API starts on port `:8080` and automatically runs database schema migrations and seeds default templates.

### 3. Compile & Run the Node Daemon
```bash
cd daemon
go build -o /usr/local/bin/astrial-daemon .
astrial-daemon
```
The daemon starts on port `:8081` and connects to `/var/run/docker.sock`.

### 4. Start the Web Frontend
Serve `web-php` via Nginx or PHP's built-in development server:
```bash
cd web-php
php -S 0.0.0.0:8000
```
Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## Automated Verification & Testing

Astrial Panel includes a comprehensive end-to-end automated verification suite using Playwright:

```bash
python3 tests/e2e/test_gamepanel_e2e.py
```

The test suite validates:
1. Unauthenticated route protection and login redirection.
2. Invalid credential rejection and error messaging.
3. Client portal isolation (strict hiding of administrative infrastructure).
4. Administrator console elevation and user fleet management.
5. Live Zero-Rebuild Theme Studio (DOM CSS variables updated dynamically).
6. 44+ game egg catalog rendering and custom Pterodactyl `.json` egg import.
7. WSL node telemetry and hardware health monitoring.
8. Automated server provisioning, Docker container boot, and WebSocket TTY command execution.
9. Billing suspension/unsuspension lifecycle and transactional SMTP email delivery in Mailpit.
10. Monaco-style file manager reading, editing, and disk persistence.

---

## License

Astrial Panel is open-source software licensed under the MIT License.
