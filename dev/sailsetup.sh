#!/bin/sh

# Run only for the first time to setup the sail environment

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php81-composer:latest \
    composer install --ignore-platform-reqs &&

cp .env.example .env &&

# alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail' &&
./vendor/bin/sail up -d &&
./vendor/bin/sail composer install &&
./vendor/bin/sail artisan key:generate &&
./vendor/bin/sail npm install &&
./vendor/bin/sail artisan storage:link &&
./vendor/bin/sail artisan migrate:fresh --seed && 
./vendor/bin/sail npm run dev