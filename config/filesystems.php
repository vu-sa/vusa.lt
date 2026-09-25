<?php

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

    'sharepoint' => [
        'client_id' => env('SHAREPOINT_CLIENT_ID'),
        'client_secret' => env('SHAREPOINT_CLIENT_SECRET'),
        'list_id' => env('SHAREPOINT_LIST_ID'),
        'main_term_set_id' => env('SHAREPOINT_MAIN_TERM_SET_ID'),
        'redirect' => env('SHAREPOINT_REDIRECT_URI'),
        'site_id' => env('SHAREPOINT_SITE_ID'),
        'tenant_id' => env('SHAREPOINT_TENANT_ID'),
        'archive_drive_id' => env('SHAREPOINT_ARCHIVE_DRIVE_ID'),
        'vusa_drive_id' => env('SHAREPOINT_VUSA_DRIVE_ID'),

        // Staging may only write to these sites (the test site). Empty everywhere else.
        'writable_site_ids' => array_values(array_filter(explode(',', (string) env('SHAREPOINT_WRITABLE_SITE_IDS', '')))),

        // Hard-coded, not env-driven, so a production .env copied to staging still fails closed.
        'production' => [
            'client_id' => '0a8deef8-374f-4a29-8f26-d0eb16062464',
            'site_ids' => ['cbb85cec-3f73-4867-8101-446082b58722'],
            'drive_ids' => [
                'b!7Fy4y3M_Z0iBAURggrWHIqgF1hPcnhJHsPCb0cd27VC7sDDnG_SQTqWB1gU9N7tB',
                'b!pMfaXjYdIEy8zqO3LWICz9geSweNHJhMi7VW4z5KDW0k2jqzC_i8TaX9RPnDbkJq',
            ],
        ],
    ],

];
