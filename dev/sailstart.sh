#!/bin/sh

# You can run this to start the sail environment

alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
sail up -d
sail npm run dev