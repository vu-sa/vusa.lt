#!/bin/sh

# Run only for the first time to setup the sail environment

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php81-composer:latest \
    composer install --ignore-platform-reqs &&

cp .env.example .env &&

alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail' &&
sail up -d &&
sail composer install &&
sail artisan key:generate &&
sail npm install &&
sail artisan storage:link &&
sail artisan migrate:fresh --seed && 
sail npm run dev