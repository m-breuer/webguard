#!/usr/bin/env sh
set -eu

case "${APP_ENV:-production}" in
    production)
        case "${APP_KEY:-}" in
            ""|"null")
                echo "ERROR: APP_KEY must be set to a persistent Laravel application key in production." >&2
                echo "Generate one with: php artisan key:generate --show" >&2
                exit 1
                ;;
        esac
        ;;
esac
