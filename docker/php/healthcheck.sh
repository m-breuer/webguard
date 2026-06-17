#!/usr/bin/env sh
set -eu

healthcheck_url="${HEALTHCHECK_URL:-http://127.0.0.1:${DOCKER_PHP_INTERNAL_PORT:-8080}/status}"
healthcheck_timeout="${HEALTHCHECK_TIMEOUT_SECONDS:-3}"

HEALTHCHECK_URL="$healthcheck_url" php -d "default_socket_timeout=${healthcheck_timeout}" -r '
$url = getenv("HEALTHCHECK_URL") ?: "http://127.0.0.1:8080/status";
$headers = @get_headers($url);

if ($headers === false || ! isset($headers[0]) || preg_match("/^HTTP\/\S+\s+2\d\d\b/", (string) $headers[0]) !== 1) {
    exit(1);
}
'
