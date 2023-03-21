#!/bin/sh

# Run only for the first time to setup the sail environment

cp .env.example .env &&

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php81-composer:latest \
    composer install --ignore-platform-reqs

# alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail' &&
./vendor/bin/sail up -d &&
./vendor/bin/sail composer install &&
./vendor/bin/sail artisan key:generate &&
./vendor/bin/sail npm install

# check if public/storage is linked
if [ ! -L public/storage ]; then
    ./vendor/bin/sail artisan storage:link
fi

./vendor/bin/sail artisan migrate:fresh --seed &&
./vendor/bin/sail npm run dev
