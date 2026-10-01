#!/bin/bash
# Claude Code on the web: Docker Hub pulls are rate-limited, so Sail cannot start.
# Install dependencies natively; tests run on in-memory SQLite (phpunit.xml).
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "$CLAUDE_PROJECT_DIR"

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

composer install --no-interaction --no-progress --prefer-dist

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --no-interaction
fi

npm install --no-audit --no-fund
