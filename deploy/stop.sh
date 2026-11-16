#!/bin/bash
pkill -f gamepanel-api || true
pkill -f gamepanel-daemon || true
docker rm -f gamepanel-web 2>/dev/null || true
docker-compose -f "/home/ubuntu/game panel/docker-compose.dev.yml" down || true
echo "All services stopped."
