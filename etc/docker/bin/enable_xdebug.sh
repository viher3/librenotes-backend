#!/bin/bash
echo "Enabling Xdebug in Infrastructure API project ..."

# Get current script execution path
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# File to remove
ROOT_PATH="$SCRIPT_PATH/../../.."
FILE="$ROOT_PATH/docker-compose.override.yml"

# Check file exists
if [ -f "$FILE" ]; then
    rm "$FILE"
fi

# Restart docker container
docker compose down symfony-ddd-core-api-php
docker compose up symfony-ddd-core-api-php -d
docker compose restart symfony-ddd-core-api-server

echo "Xdebug enabled."
