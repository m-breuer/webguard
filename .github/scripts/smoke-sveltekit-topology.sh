#!/usr/bin/env sh

set -eu

project_name="${SMOKE_PROJECT_NAME:-webguard-sveltekit-smoke}"
network_name="${WEBGUARD_NETWORK:-${project_name}-network}"
compose="docker compose --project-name ${project_name} --profile internal-services -f docker-compose.yml"

cleanup() {
    $compose logs --no-color || true
    $compose down --volumes --remove-orphans || true
    docker network rm "$network_name" || true
}

trap cleanup EXIT INT TERM

docker network create "$network_name"

export WEBGUARD_NETWORK="$network_name"
export APP_KEY="${APP_KEY:-base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=}"
export MARKETING_URL="${MARKETING_URL:-https://marketing.webguard.test}"
export SERVICE_URL_PHP="${SERVICE_URL_PHP:-http://gateway:8080}"

$compose up --build --detach --wait --wait-timeout 180 php frontend gateway schedule queue-default mysql redis

$compose exec --no-TTY gateway wget --quiet --output-document=/dev/null http://127.0.0.1:8080/_health/gateway
$compose exec --no-TTY gateway wget --quiet --output-document=/dev/null http://127.0.0.1:8080/_health/frontend
$compose exec --no-TTY gateway wget --quiet --output-document=/dev/null http://127.0.0.1:8080/_health/laravel
$compose exec --no-TTY queue-default healthcheck-queue
$compose exec --no-TTY schedule php artisan schedule:list --no-interaction --no-ansi
