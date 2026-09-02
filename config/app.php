<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    */

    'name' => env('APP_NAME', 'VU SA'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'https://www.vusa.lt'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Staging Environment Protection
    |--------------------------------------------------------------------------
    |
    | Credentials for HTTP Basic Auth protection on staging environment.
    | Only used when APP_ENV=staging.
    |
    */

    'staging_user' => env('STAGING_USER'),

    'staging_password' => env('STAGING_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Staging Database Refresh
    |--------------------------------------------------------------------------
    |
    | Used by `staging:refresh-database`, which replaces staging's database with
    | the newest production backup. Production and staging share a VPS, so the
    | source is a local path rather than a transfer.
    |
    | Every address outside the allowlist is rewritten to user{id}@staging.invalid
    | on import, so staging's schedule cannot mail real students. Keep the list to
    | the people who need to receive staging mail.
    |
    */

    'staging_refresh' => [
        'source_backup_dir' => env('STAGING_SOURCE_BACKUP_DIR'),
        'email_allowlist' => env('STAGING_EMAIL_ALLOWLIST', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Staging Read-Only Modes
    |--------------------------------------------------------------------------
    |
    | When staging shares resources with production, enable read-only mode
    | to prevent accidental modifications.
    |
    */

    'files_read_only' => env('FILES_READ_ONLY', false),

    'sharepoint_read_only' => env('SHAREPOINT_READ_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('TIMEZONE', 'Europe/Vilnius'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'lt',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    // Locales
    'locales' => ['lt', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

];
