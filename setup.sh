#!/bin/sh

composer update
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail composer update
./vendor/bin/sail artisan key:generate
./vendor/bin/sail npm update
./vendor/bin/sail artisan storage:link
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm run dev & disown