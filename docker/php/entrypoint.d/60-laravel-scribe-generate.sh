#!/usr/bin/env sh
set -eu

if [ "${AUTORUN_LARAVEL_SCRIBE_GENERATE:-false}" != "true" ]; then
  exit 0
fi

APP_BASE_DIR="${APP_BASE_DIR:-/var/www/html}"

if [ ! -f "$APP_BASE_DIR/artisan" ]; then
  echo "Artisan file not found in $APP_BASE_DIR"
  exit 1
fi

php "$APP_BASE_DIR/artisan" scribe:generate --force
