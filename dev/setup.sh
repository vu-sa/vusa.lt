#!/bin/sh
set -eu

usage() {
    printf 'Usage: %s [--yes|-y]\n' "$0"
    printf '%s\n' 'Set up the local Sail environment. --yes skips prompts and preserves existing data.'
}

yes=false
while [ "$#" -gt 0 ]; do
    case $1 in
        --yes|-y) yes=true ;;
        --help|-h) usage; exit 0 ;;
        *) usage >&2; exit 2 ;;
    esac
    shift
done

cd "$(dirname "$0")/.."

sail() {
    vendor/bin/sail "$@"
}

env_has_value() {
    awk -v key="$1" '
        $0 ~ "^[[:space:]]*(export[[:space:]]+)?" key "[[:space:]]*=" {
            value = $0
            sub(/^[^=]*=[[:space:]]*/, "", value)
            sub(/[[:space:]]+#.*/, "", value)
            sub(/[[:space:]]+$/, "", value)
        }
        END {
            quote = sprintf("%c", 39)
            exit (value == "" || value ~ /^#/ || value ~ /^"[[:space:]]*"$/ ||
                  value ~ ("^" quote "[[:space:]]*" quote "$"))
        }
    ' .env
}

if [ ! -e .env ]; then
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/var/www/html -w /var/www/html \
        composer:2 composer install --ignore-platform-reqs --no-scripts
fi

sail up -d --wait --wait-timeout 120
attempt=0
until sail exec -T laravel.test true >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        printf '%s\n' 'Timed out waiting for laravel.test to accept Sail commands.' >&2
        exit 1
    fi
    sleep 2
done

needs_app_key=false
needs_search_keys=false
if ! env_has_value APP_KEY; then
    needs_app_key=true
fi
if ! env_has_value TYPESENSE_SEARCH_ONLY_KEY || ! env_has_value TYPESENSE_ADMIN_SEARCH_KEY; then
    needs_search_keys=true
fi
if [ -f bootstrap/cache/config.php ] && { [ "$needs_app_key" = true ] || [ "$needs_search_keys" = true ]; }; then
    sail artisan config:clear
fi

sail composer install --no-interaction

if [ "$needs_app_key" = true ]; then
    if ! grep -Fxq 'APP_KEY=' .env; then
        # Laravel replaces the literal APP_KEY= assignment.
        env_copy=$(mktemp)
        trap 'rm -f "$env_copy"' EXIT
        sed -E '/^[[:space:]]*(export[[:space:]]+)?APP_KEY[[:space:]]*=/d' .env > "$env_copy"
        printf '\nAPP_KEY=\n' >> "$env_copy"
        cat "$env_copy" > .env
        rm -f "$env_copy"
        trap - EXIT
    fi
    sail artisan key:generate --no-interaction
fi
if [ ! -d node_modules ] || [ ! -f node_modules/.package-lock.json ] ||
    [ package-lock.json -nt node_modules/.package-lock.json ]; then
    sail npm ci
fi
if [ ! -L public/storage ]; then
    sail artisan storage:link
fi

if migration_status=$(sail artisan migrate:status --no-ansi --no-interaction 2>&1); then
    rebuild=
    if [ "$yes" = false ] && [ -t 0 ] && [ -t 1 ]; then
        printf 'Drop all local tables and reseed the database? [y/N] '
        IFS= read -r rebuild || rebuild=
    fi
    case $rebuild in
        y|Y|yes|Yes|YES) sail artisan migrate:fresh --seed --no-interaction ;;
        *) sail artisan migrate --no-interaction ;;
    esac
elif printf '%s\n' "$migration_status" | grep -Fq 'Migration table not found.'; then
    sail artisan migrate --seed --no-interaction
else
    printf '%s\n' "$migration_status" >&2
    exit 1
fi

if ! env_has_value TYPESENSE_SEARCH_ONLY_KEY; then
    sail artisan typesense:generate-search-key --no-interaction
fi
if ! env_has_value TYPESENSE_ADMIN_SEARCH_KEY; then
    sail artisan typesense:generate-admin-search-key --no-interaction
fi
if [ "$needs_search_keys" = true ]; then
    sail artisan config:clear
fi

aliases=$(sed -n "s/.*'alias'[[:space:]]*=>[[:space:]]*'\([^']*\)'.*/\1/p" database/seeders/TenantSeeder.php)
printf '\nAdd this line to your hosts file:\n  127.0.0.1 www.vusa.test vusa.test'
for alias in $aliases; do
    printf ' %s.vusa.test' "$alias"
done
printf '\n\n%s\n' \
    'Copy the local CA certificate out of nginx:' \
    '  vendor/bin/sail cp nginx:/etc/nginx/certs/root-ca.crt .' \
    'Import root-ca.crt into your system or browser certificate store as a trusted root CA for websites.' \
    'Test login: test@test.com / password' \
    'Start Vite: vendor/bin/sail npm run dev'
