#!/usr/bin/env sh

set -eu

if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <base-sha> <head-sha>" >&2
    exit 64
fi

base_sha="$1"
head_sha="$2"

if ! changed_files="$(git diff --name-only --diff-filter=ACMR "$base_sha" "$head_sha")"; then
    echo "true"
    exit 0
fi

while IFS= read -r changed_file; do
    case "$changed_file" in
        .github/scripts/*|.github/workflows/*|Dockerfile|docker/*|docker-compose*.yml|compose*.yml|start-dev.sh|composer.json|composer.lock|package.json|bun.lock|frontend/package.json|frontend/vite.config.*|frontend/svelte.config.*|frontend/src/*|routes/*|app/Console/*|app/Http/*|app/Jobs/*|app/Providers/*|bootstrap/*|config/*|public/*|database/migrations/*|database/seeders/*)
            echo "true"
            exit 0
            ;;
    esac
done <<EOF
$changed_files
EOF

echo "false"
