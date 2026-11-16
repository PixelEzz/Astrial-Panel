#!/bin/bash
set -e
export PATH=/home/ubuntu/.local/bin:$PATH

echo "1. Ensuring Docker services (Postgres, Redis, Mailpit)..."
docker-compose -f "/home/ubuntu/game panel/docker-compose.dev.yml" up -d
docker run --rm -v /srv:/srv alpine:latest chmod -R 777 /srv/daemon 2>/dev/null || true

echo "2. Starting Node Daemon on :8081..."
pkill -f gamepanel-daemon || true
setsid /home/ubuntu/.local/bin/gamepanel-daemon </dev/null >/tmp/daemon.log 2>&1 &
sleep 1

echo "3. Starting Control Plane API on :8080..."
pkill -f gamepanel-api || true
setsid /home/ubuntu/.local/bin/gamepanel-api </dev/null >/tmp/api.log 2>&1 &
sleep 2

echo "4. Starting Nginx + PHP-FPM Web Frontend on :8000 & :80..."
docker rm -f gamepanel-web 2>/dev/null || true
docker run -d --name gamepanel-web --network host -v "/home/ubuntu/game panel/web-php:/var/www/html" local/gamepanel-web:latest >/dev/null
sleep 1

echo "5. Checking health..."
curl -s http://localhost:8081/api/system | grep -q healthy && echo " -> Node Daemon: HEALTHY (:8081)"
curl -s http://localhost:8080/api/theme | grep -q primary_color && echo " -> Control Plane API: HEALTHY (:8080)"
curl -s -I http://localhost:8000/ | grep -q "200 OK" && echo " -> Nginx + PHP-FPM Web Frontend: HEALTHY (:8000, :80)"
