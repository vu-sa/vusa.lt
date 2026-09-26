<?php

$sharepointProductionSite = [
    'site_id' => 'cbb85cec-3f73-4867-8101-446082b58722',
    'list_id' => 'e730b0bb-f41b-4e90-a581-d6053d37bb41',
    'drive_id' => 'b!7Fy4y3M_Z0iBAURggrWHIqgF1hPcnhJHsPCb0cd27VC7sDDnG_SQTqWB1gU9N7tB',
];

$sharepointTestSite = [
    'site_id' => 'b2335e40-b91e-40ca-8b73-eeafbce92be0',
    'list_id' => '02f0dec3-1f09-4ec8-aca0-eb90b1db814e',
    'drive_id' => 'b!QF4zsh65ykCLc-6vvOkr4AP0wmTErtxEnKFUYKdmKJHD3vACCR_ITqyg65Cx24FO',
];

$sharepointSite = env('APP_ENV') === 'production' ? $sharepointProductionSite : $sharepointTestSite;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/uploads',
            'visibility' => 'public',
        ],

        'spatieMediaLibrary' => [
            'driver' => 'local',
            'root' => storage_path('app/public/media'),
            'url' => env('APP_URL').'/uploads/media',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('uploads') => storage_path('app/public'),
    ],

    /*
    | Only the app credentials are secret. The IDs default to production on production and to the
    | test site everywhere else, so .env only needs them to point somewhere unusual.
    */
    'sharepoint' => [
        'client_id' => env('SHAREPOINT_CLIENT_ID'),
        'client_secret' => env('SHAREPOINT_CLIENT_SECRET'),
        'tenant_id' => env('SHAREPOINT_TENANT_ID', '7ac8575d-ef21-4c2f-a125-2c7a2ddb24d6'),
        'site_id' => env('SHAREPOINT_SITE_ID', $sharepointSite['site_id']),
        'list_id' => env('SHAREPOINT_LIST_ID', $sharepointSite['list_id']),
        'vusa_drive_id' => env('SHAREPOINT_VUSA_DRIVE_ID', $sharepointSite['drive_id']),
        // Production's document archive everywhere; staging's Entra app may only read it.
        'archive_drive_id' => env('SHAREPOINT_ARCHIVE_DRIVE_ID', 'b!pMfaXjYdIEy8zqO3LWICz9geSweNHJhMi7VW4z5KDW0k2jqzC_i8TaX9RPnDbkJq'),

        // Staging may only write to these sites (the test site). Empty everywhere else.
        'writable_site_ids' => array_values(array_filter(explode(',', (string) env(
            'SHAREPOINT_WRITABLE_SITE_IDS',
            env('APP_ENV') === 'staging' ? $sharepointTestSite['site_id'] : '',
        )))),

        // Hard-coded, not env-driven, so a production .env copied to staging still fails closed.
        'production' => [
            'client_id' => '0a8deef8-374f-4a29-8f26-d0eb16062464',
            'site_ids' => [$sharepointProductionSite['site_id']],
            'drive_ids' => [
                $sharepointProductionSite['drive_id'],
                'b!pMfaXjYdIEy8zqO3LWICz9geSweNHJhMi7VW4z5KDW0k2jqzC_i8TaX9RPnDbkJq',
            ],
        ],
    ],

];
