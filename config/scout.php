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
    | source search engine that provides fast, typo-tolerant search.
    |
    | For production, ensure you:
    | 1. Set a strong TYPESENSE_API_KEY
    | 2. Configure TYPESENSE_SEARCH_ONLY_KEY for frontend use
    | 3. Use HTTPS protocol for external connections
    | 4. Review collection schemas for your content types
    |
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
            'search_only_key' => env('TYPESENSE_SEARCH_ONLY_KEY'),
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
        /*
        |--------------------------------------------------------------------------
        | Model-specific Typesense Configurations
        |--------------------------------------------------------------------------
        |
        | Define collection schemas and search parameters for each searchable model.
        | Only public, published content should be indexed for security.
        |
        | Schema fields:
        | - name: Field name in Typesense
        | - type: Data type (string, int32, int64, bool, auto)
        | - facet: Enable faceted search (true/false)
        | - optional: Allow missing values (true/false)
        | - sort: Enable sorting on this field (true/false)
        |
        */
        'model-settings' => [

            // News Articles - Only published, non-draft content
            \App\Models\News::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'short', 'type' => 'string', 'optional' => true],
                        ['name' => 'permalink', 'type' => 'string'],
                        ['name' => 'image', 'type' => 'string', 'optional' => true],
                        ['name' => 'publish_time', 'type' => 'int64'],
                        ['name' => 'lang', 'type' => 'string', 'facet' => true],
                        ['name' => 'tenant_name', 'type' => 'string', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'publish_time',
                ],
                'search-parameters' => [
                    'query_by' => 'title,short',
                    'query_by_weights' => '4,2',
                ],
            ],

            // Static Pages - Only published pages
            \App\Models\Page::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'permalink', 'type' => 'string'],
                        ['name' => 'lang', 'type' => 'string', 'facet' => true],
                        ['name' => 'tenant_name', 'type' => 'string', 'facet' => true],
                        ['name' => 'category_name', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title',
                    'query_by_weights' => '4',
                ],
            ],

            // Calendar Events - Only public events with multilingual support
            \App\Models\Calendar::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'title_lt', 'type' => 'string', 'locale' => 'lt', 'optional' => true],
                        ['name' => 'title_en', 'type' => 'string', 'locale' => 'en', 'optional' => true],
                        ['name' => 'date', 'type' => 'int64', 'sort' => true],
                        ['name' => 'end_date', 'type' => 'int64', 'optional' => true],
                        ['name' => 'lang', 'type' => 'string', 'facet' => true],
                        ['name' => 'tenant_name', 'type' => 'string', 'facet' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'date',
                ],
                'search-parameters' => [
                    'query_by' => 'title,title_lt,title_en',
                    'query_by_weights' => '5,4,4',
                ],
            ],

            // Documents - Only public documents with metadata
            \App\Models\Document::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'summary', 'type' => 'string', 'optional' => true],
                        ['name' => 'language', 'type' => 'string', 'facet' => true],
                        ['name' => 'content_type', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'institution_name_lt', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'institution_name_en', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'tenant_shortname', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'document_date', 'type' => 'int64', 'facet' => true, 'sort' => true],
                        ['name' => 'anonymous_url', 'type' => 'string'],
                        ['name' => 'is_active', 'type' => 'bool'],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'document_date',
                ],
                'search-parameters' => [
                    'query_by' => 'title,summary',
                    'query_by_weights' => '3,2',
                ],
            ],
        ],
    ],

];
