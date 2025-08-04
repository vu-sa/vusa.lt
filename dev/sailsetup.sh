#!/bin/sh

# Run only for the first time to setup the sail environment

cp .env.example .env &&

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs

# alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail' &&
./vendor/bin/sail up -d &&
./vendor/bin/sail composer install &&
./vendor/bin/sail artisan key:generate &&
./vendor/bin/sail npm ci

./vendor/bin/sail artisan vendor:publish --provider="Octopy\Impersonate\ImpersonateServiceProvider"

# check if public/storage is linked
if [ ! -L public/storage ]; then
    ./vendor/bin/sail artisan storage:link
fi

touch database/database.sqlite &&

./vendor/bin/sail artisan migrate:fresh --seed &&

# Install browser dependencies and Playwright browsers for frontend testing
echo "Installing browser system dependencies..."
./vendor/bin/sail exec laravel.test apt-get update &&
./vendor/bin/sail exec laravel.test apt-get install -y libnspr4 libnss3 libatk-bridge2.0-0 libdrm2 libxkbcommon0 libxcomposite1 libxdamage1 libxrandr2 libgbm1 libxss1 libasound2t64 &&

echo "Installing Playwright browsers for testing..."
./vendor/bin/sail npx playwright install &&

./vendor/bin/sail npm run dev
