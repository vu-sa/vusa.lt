<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
     * Self-hosted Umami analytics. The tracker script tag is rendered server-side in
     * app.blade.php, so these are plain (non-VITE_) env values: changing them needs no
     * frontend rebuild. An empty 'website_id' disables tracking entirely (staging, CI).
     */
    'umami' => [
        'script_url' => env('UMAMI_SCRIPT_URL'),
        'website_id' => env('UMAMI_WEBSITE_ID'),

        /*
         * Server-to-server API access, used by UmamiClient for the admin dashboard.
         * Point this at loopback rather than the public URL: it avoids a hairpin through
         * nginx and keeps working if the public vhost is down. It must include Umami's
         * BASE_PATH when one was baked in at build time (production: /analytics).
         */
        'api_url' => env('UMAMI_API_URL'),
        'username' => env('UMAMI_USERNAME'),
        'password' => env('UMAMI_PASSWORD'),
    ],

];
