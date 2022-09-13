#!/bin/sh

# You can run this to start the sail environment

# alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail' &&
./vendor/bin/sail up -d &&
./vendor/bin/sail npm run dev