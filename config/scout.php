<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "algolia", "meilisearch", "database", "collection", "null"
    |
    */

    'driver' => env('SCOUT_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => env('SCOUT_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data will only be synced
    | with your search indexes after every open database transaction has
    | been committed, thus preventing any discarded data from syncing.
    |
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this application's users.
    |
    | Supported engines: "algolia"
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Typesense settings. Typesense is an open
    | source search engine using minimal configuration. Below, you will
    | state the host, key, and schema configuration for the instance.
    |
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
            'nodes' => [
                [
                    'host' => env('TYPESENSE_HOST', 'localhost'),
                    'port' => env('TYPESENSE_PORT', '8108'),
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                ],
            ],
            'nearest_node' => [
                'host' => env('TYPESENSE_HOST', 'localhost'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'path' => env('TYPESENSE_PATH', ''),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
            'connection_timeout_seconds' => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 2),
            'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
            'num_retries' => env('TYPESENSE_NUM_RETRIES', 3),
            'retry_interval_seconds' => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
        ],
        'model-settings' => [
            // Document model schema
            \App\Models\Document::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'int32'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'name', 'type' => 'string'],
                        ['name' => 'summary', 'type' => 'string', 'optional' => true],
                        ['name' => 'content_type', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'language', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'institution_name_lt', 'type' => 'string', 'locale' => 'lt', 'facet' => true, 'optional' => true],
                        ['name' => 'institution_name_en', 'type' => 'string', 'locale' => 'en', 'facet' => true, 'optional' => true],
                        ['name' => 'document_date', 'type' => 'int64', 'facet' => true, 'sort' => true, 'optional' => true],
                        ['name' => 'effective_date', 'type' => 'int64', 'optional' => true],
                        ['name' => 'expiration_date', 'type' => 'int64', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title',
                    'query_by_weights' => '3'
                ],
            ],
            
            // News model schema
            \App\Models\News::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'int32'],
                        ['name' => 'title', 'type' => 'string', 'sort' => true],
                        ['name' => 'short', 'type' => 'string', 'optional' => true],
                        // ['name' => 'content', 'type' => 'string', 'optional' => true],
                        ['name' => 'permalink', 'type' => 'string', 'optional' => true],
                        ['name' => 'image', 'type' => 'string', 'optional' => true],
                        ['name' => 'publish_time', 'type' => 'int64', 'optional' => true],
                        ['name' => 'lang', 'type' => 'string', 'facet' => true],
                        ['name' => 'tenant.id', 'type' => 'int32', 'facet' => true],
                        ['name' => 'tenant.name', 'type' => 'string', 'optional' => true],
                        // ['name' => 'draft', 'type' => 'bool', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title,short',
                    'query_by_weights' => '4,2',
                ],
            ],
            
            // Page model schema
            \App\Models\Page::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'permalink', 'type' => 'string', 'optional' => true],
                        // ['name' => 'content', 'type' => 'string', 'optional' => true],
                        ['name' => 'lang', 'type' => 'string', 'facet' => true],
                        ['name' => 'tenant_id', 'type' => 'int32', 'facet' => true],
                        ['name' => 'tenant_name', 'type' => 'string', 'optional' => true],
                        ['name' => 'category_id', 'type' => 'int32', 'facet' => true, 'optional' => true],
                        ['name' => 'category_name', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title',
                    'query_by_weights' => '4'
                ],
            ],

            \App\Models\User::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'int32'],
                        ['name' => 'name', 'type' => 'string', 'sort' => true],
                        ['name' => 'email', 'type' => 'string'],
                        ['name' => 'phone', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'name,email',
                    'query_by_weights' => '4,2',
                ],
            ],
            
            // Calendar model schema
            \App\Models\Calendar::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'int32'],
                        ['name' => 'title->lt', 'type' => 'string', 'locale' => 'lt'],
                        ['name' => 'title->en', 'type' => 'string', 'locale' => 'en'],
                        // ['name' => 'description', 'type' => 'object', 'optional' => true],
                        // ['name' => 'location', 'type' => 'object', 'optional' => true],
                        // ['name' => 'organizer', 'type' => 'object', 'optional' => true],
                        ['name' => 'date', 'type' => 'int64', 'sort' => true],
                        ['name' => 'end_date', 'type' => 'int64', 'optional' => true],
                        ['name' => 'lang', 'type' => 'string', 'facet' => true],
                        ['name' => 'tenant_id', 'type' => 'int32', 'facet' => true],
                        ['name' => 'is_draft', 'type' => 'bool', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'date',
                ],
                'search-parameters' => [
                    'query_by' => 'title->lt,title->en',
                    'query_by_weights' => '4,4'
                ],
            ],
            
            // Training model schema
            \App\Models\Training::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name->lt', 'type' => 'string', 'locale' => 'lt'],
                        ['name' => 'name->en', 'type' => 'string', 'locale' => 'en'],
                        ['name' => 'description->lt', 'type' => 'string', 'locale' => 'lt'],
                        ['name' => 'description->en', 'type' => 'string', 'locale' => 'en'],
                        ['name' => 'start_time', 'type' => 'int64', 'sort' => true],
                        ['name' => 'end_time', 'type' => 'int64', 'optional' => true],
                        ['name' => 'address', 'type' => 'string', 'optional' => true],
                        ['name' => 'meeting_url', 'type' => 'string', 'optional' => true],
                        ['name' => 'institution_id', 'type' => 'string', 'facet' => true],
                        ['name' => 'status', 'type' => 'string', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'start_time',
                ],
                'search-parameters' => [
                    'query_by' => 'name->lt,name->en,description->lt,description->en,address,meeting_url',
                    'query_by_weights' => '4,4,2,2,1,1'
                ],
            ],

            // Resource model schema
            \App\Models\Resource::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name->lt', 'type' => 'string', 'locale' => 'lt'],
                        ['name' => 'name->en', 'type' => 'string', 'locale' => 'en'],
                        ['name' => 'description->lt', 'type' => 'string', 'locale' => 'lt'],
                        ['name' => 'description->en', 'type' => 'string', 'locale' => 'en'],
                        ['name' => 'capacity', 'type' => 'int32', 'optional' => true],
                        ['name' => 'tenant_id', 'type' => 'int32', 'facet' => true],
                        ['name' => 'resource_category_id', 'type' => 'int32', 'facet' => true, 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'name->lt,name->en',
                    'query_by_weights' => '4,4'
                ],
            ],

            // ResourceCategory model schema
            \App\Models\ResourceCategory::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'int32'],
                        ['name' => 'name->lt', 'type' => 'string', 'locale' => 'lt'],
                        ['name' => 'name->en', 'type' => 'string', 'locale' => 'en'],
                        ['name' => 'description->lt', 'type' => 'string', 'locale' => 'lt'],
                        ['name' => 'description->en', 'type' => 'string', 'locale' => 'en'],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'name->lt,name->en,description->lt,description->en',
                    'query_by_weights' => '4,4,2,2'
                ],
            ],

            // Duty model schema
            \App\Models\Duty::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name->lt', 'type' => 'string', 'locale' => 'lt'],
                        ['name' => 'name->en', 'type' => 'string', 'locale' => 'en'],
                        // ['name' => 'description', 'type' => 'object', 'optional' => true],
                        ['name' => 'email', 'type' => 'string', 'optional' => true],
                        ['name' => 'institution_id', 'type' => 'string', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'name->lt,name->en,email',
                    'query_by_weights' => '4,4,3'
                ],
            ],

            // Form model schema
            \App\Models\Form::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name->lt', 'type' => 'string'],
                        ['name' => 'name->en', 'type' => 'string'],
                        ['name' => 'path->lt', 'type' => 'string'],
                        ['name' => 'path->en', 'type' => 'string'],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'name->lt,name->en,path->lt,path->en',
                    'query_by_weights' => '4,4,2,2'
                ],
            ],

            // Membership model schema
            \App\Models\Membership::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name->lt', 'type' => 'string'],
                        ['name' => 'name->en', 'type' => 'string'],
                        ['name' => 'tenant_id', 'type' => 'int32', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'name->lt,name->en',
                    'query_by_weights' => '1,1'
                ],
            ],

            // Meeting model schema
            \App\Models\Meeting::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string', 'optional' => true],
                        ['name' => 'location', 'type' => 'string', 'optional' => true],
                        ['name' => 'start_time', 'type' => 'int64', 'sort' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'start_time',
                ],
                'search-parameters' => [
                    'query_by' => 'title,description,location',
                    'query_by_weights' => '4,2,1'
                ],
            ],

            // Matter model schema
            \App\Models\Matter::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title,description',
                    'query_by_weights' => '4,2'
                ],
            ],

            // Goal model schema
            \App\Models\Goal::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string', 'optional' => true],
                        ['name' => 'tenant_id', 'type' => 'int32', 'facet' => true, 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title,description',
                    'query_by_weights' => '4,2'
                ],
            ],

            // Banner model schema
            \App\Models\Banner::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'int32'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'url', 'type' => 'string', 'optional' => true],
                        ['name' => 'tenant_id', 'type' => 'int32', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title',
                    'query_by_weights' => '1'
                ],
            ],

            // QuickLink model schema
            \App\Models\QuickLink::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'int32'],
                        ['name' => 'text', 'type' => 'string'],
                        ['name' => 'link', 'type' => 'string'],
                        ['name' => 'tenant_id', 'type' => 'int32', 'facet' => true],
                        ['name' => 'lang', 'type' => 'string', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'text,link',
                    'query_by_weights' => '2,1'
                ],
            ],

            // Reservation model schema
            \App\Models\Reservation::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string', 'optional' => true],
                        ['name' => 'start_time', 'type' => 'int64', 'sort' => true],
                        ['name' => 'end_time', 'type' => 'int64'],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'start_time',
                ],
                'search-parameters' => [
                    'query_by' => 'name,description',
                    'query_by_weights' => '4,2'
                ],
            ],

            // Institution model schema
            \App\Models\Institution::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name->lt', 'type' => 'string'],
                        ['name' => 'name->en', 'type' => 'string'],
                        ['name' => 'short_name->lt', 'type' => 'string', 'optional' => true],
                        ['name' => 'short_name->en', 'type' => 'string', 'optional' => true],
                        ['name' => 'tenant_id', 'type' => 'int32', 'facet' => true],
                        ['name' => 'is_active', 'type' => 'bool', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                        ['name' => 'deleted_at', 'type' => 'int64', 'optional' => true, 'facet' => true],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'name->lt,name->en,short_name->lt,short_name->en',
                    'query_by_weights' => '4,4,3,3'
                ],
            ],

            // Doing model schema
            \App\Models\Doing::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string', 'optional' => true],
                        ['name' => 'state', 'type' => 'string', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title,description',
                    'query_by_weights' => '4,2'
                ],
            ],

            // Tenant model schema
            \App\Models\Tenant::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'int32'],
                        ['name' => 'fullname', 'type' => 'string', 'sort' => true],
                        ['name' => 'shortname', 'type' => 'string'],
                        ['name' => 'alias', 'type' => 'string'],
                        ['name' => 'type', 'type' => 'string', 'facet' => true],
                    ],
                    'default_sorting_field' => 'fullname',
                ],
                'search-parameters' => [
                    'query_by' => 'fullname,shortname,alias',
                    'query_by_weights' => '4,3,3'
                ],
            ],
        ],
    ],

];
