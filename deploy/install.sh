#!/usr/bin/env bash
set -e

# ==============================================================================
# Astrial Panel - Automated Production Host Installer (Ubuntu / Debian)
# ==============================================================================

echo "======================================================================"
echo "          Astrial Panel - Production Host Infrastructure Installer    "
echo "======================================================================"

if [ "$EUID" -ne 0 ]; then
  echo "Error: Please run this installer as root (e.g. sudo bash install.sh)."
  exit 1
fi

# Detect Server IP
SERVER_IP=$(curl -s https://api.ipify.org || hostname -I | awk '{print $1}')
echo "-> Detected Server Public IP: ${SERVER_IP}"

read -p "Enter Panel Public Domain/URL [http://${SERVER_IP}]: " INPUT_DOMAIN
PANEL_DOMAIN=${INPUT_DOMAIN:-"http://${SERVER_IP}"}

read -p "Enter Database Host [127.0.0.1]: " INPUT_DB_HOST
DB_HOST=${INPUT_DB_HOST:-"127.0.0.1"}

read -p "Enter Database Port [5432]: " INPUT_DB_PORT
DB_PORT=${INPUT_DB_PORT:-"5432"}

read -p "Enter Database Name [astrial_panel]: " INPUT_DB_NAME
DB_NAME=${INPUT_DB_NAME:-"astrial_panel"}

read -p "Enter Database User [astrial]: " INPUT_DB_USER
DB_USER=${INPUT_DB_USER:-"astrial"}

# Generate secure random DB password if not provided
DEFAULT_DB_PASS=$(openssl rand -hex 16)
read -p "Enter Database Password [auto-generated]: " INPUT_DB_PASS
DB_PASS=${INPUT_DB_PASS:-"${DEFAULT_DB_PASS}"}

echo "-> Updating system packages and installing prerequisites..."
apt-get update -qq
apt-get install -y -qq curl wget git nginx php-fpm php-curl php-json php-mbstring postgresql-client docker.io docker-compose

echo "-> Writing production .env configuration..."
cat <<EOF > /etc/astrial.env
APP_ENV=production
APP_URL=${PANEL_DOMAIN}
APP_NAME="Astrial Panel"

# Database Configuration
DATABASE_URL=postgres://${DB_USER}:${DB_PASS}@${DB_HOST}:${DB_PORT}/${DB_NAME}?sslmode=disable
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASS}
DB_SSLMODE=disable

# Control Plane API & Daemons
API_PORT=8080
PANEL_API_URL=http://127.0.0.1:8080
DAEMON_PORT=8081
EOF

echo "-> Setting up Nginx Virtual Host for ${PANEL_DOMAIN}..."
cat <<EOF > /etc/nginx/sites-available/astrial.conf
server {
    listen 80;
    server_name _;

    root /var/www/astrial/web-php;
    index index.php index.html;

    # Reverse proxy API requests to Go Control Plane
    location /api/ {
        proxy_pass http://127.0.0.1:8080/api/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_read_timeout 86400s;
        proxy_send_timeout 86400s;
    }

    # PHP-FPM FastCGI
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
}
EOF

ln -sf /etc/nginx/sites-available/astrial.conf /etc/nginx/sites-enabled/astrial.conf
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
systemctl restart nginx 2>/dev/null || true

echo "======================================================================"
echo " Astrial Panel System Services Ready!"
echo " Web Setup Wizard: ${PANEL_DOMAIN}/install.php"
echo " (Open your browser to complete database initialization and admin account setup)"
echo "======================================================================"
