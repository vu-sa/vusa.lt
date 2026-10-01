#!/bin/bash
# Claude Code on the web: Docker Hub pulls are rate-limited, so Sail cannot start.
# Install dependencies natively; tests run on in-memory SQLite (phpunit.xml).
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "$CLAUDE_PROJECT_DIR"

# The container runs as root, and Composer skips plugins for root unless told otherwise;
# without pestphp/pest-plugin, Pest loses --parallel and TIA.
export COMPOSER_ALLOW_SUPERUSER=1

if [ ! -f .env ]; then
  cp .env.example .env
  sed -i \
    -e 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' \
    -e "s|^DB_DATABASE=.*|DB_DATABASE=$CLAUDE_PROJECT_DIR/database/database.sqlite|" \
    -e 's/^CACHE_DRIVER=.*/CACHE_DRIVER=file/' \
    -e 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' \
    -e 's/^BROADCAST_CONNECTION=.*/BROADCAST_CONNECTION=log/' \
    -e 's/^MAIL_MAILER=.*/MAIL_MAILER=log/' \
    -e 's/^TELESCOPE_ENABLED=.*/TELESCOPE_ENABLED=false/' \
    .env
fi
touch database/database.sqlite

# GitHub zipballs are blocked; install from git and seed the dist-only packages.
php .claude/hooks/seed-composer-dist-cache.php
composer install --no-interaction --no-progress --prefer-source

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --no-interaction
fi

# npm ci leaves package-lock.json alone; npm install here (npm 10) rewrites it.
npm ci --no-audit --no-fund

# Feature tests that render error pages need the Vite manifest.
npm run build

# Pest TIA resolves the default branch through origin/HEAD, which a fresh clone lacks.
if ! git symbolic-ref -q refs/remotes/origin/HEAD >/dev/null; then
  git fetch -q origin main && git remote set-head origin main
fi
