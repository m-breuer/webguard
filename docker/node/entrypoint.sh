#!/usr/bin/env sh
set -eu

cd /var/www/html
export HOME="${HOME:-/tmp}"
export XDG_CACHE_HOME="${XDG_CACHE_HOME:-/tmp/.cache}"
export BUN_INSTALL_CACHE_DIR="${BUN_INSTALL_CACHE_DIR:-/tmp/.bun/install/cache}"

mkdir -p "$XDG_CACHE_HOME"
mkdir -p "$BUN_INSTALL_CACHE_DIR"
mkdir -p /var/www/html/node_modules

if [ ! -d node_modules ] || [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
  bun install --frozen-lockfile
fi

if ! ls node_modules/@rollup/rollup-linux-* >/dev/null 2>&1; then
  rm -rf node_modules
  bun install --frozen-lockfile
fi

exec "$@"
