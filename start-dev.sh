#!/bin/bash
set -euo pipefail

SERVICE="php"
USER_ID=$(id -u)
GROUP_ID=$(id -g)
export USER_ID GROUP_ID

compose() {
  docker compose -f docker-compose.yml -f docker-compose.override.yml "$@"
}

echo "Starting local development environment..."
rm -f bootstrap/cache/*.php
compose up -d --build
echo "Local development environment started."

echo "Connecting to shell in container ${SERVICE} ..."
if compose exec -T "${SERVICE}" sh -lc "command -v bash >/dev/null 2>&1"; then
  compose exec "${SERVICE}" bash
  exit 0
fi

compose exec "${SERVICE}" sh
