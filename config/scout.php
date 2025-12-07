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
            'api_key' => env('TYPESENSE_API_KEY'),
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
                        ['name' => 'title', 'type' => 'string', 'infix' => true],
                        ['name' => 'short', 'type' => 'string', 'optional' => true, 'infix' => true],
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
                    'query_by_weights' => '10,4',
                ],
            ],

            // Static Pages - Only published pages
            \App\Models\Page::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string', 'infix' => true],
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
                    'query_by_weights' => '10',
                ],
            ],

            // Calendar Events - Only public events with multilingual support
            \App\Models\Calendar::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string', 'infix' => true],
                        ['name' => 'title_lt', 'type' => 'string', 'locale' => 'lt', 'optional' => true, 'infix' => true],
                        ['name' => 'title_en', 'type' => 'string', 'locale' => 'en', 'optional' => true, 'infix' => true],
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
                    'query_by_weights' => '10,8,8',
                ],
            ],

            // Documents - Only public documents with metadata
            \App\Models\Document::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string', 'infix' => true, 'sort' => true],
                        ['name' => 'summary', 'type' => 'string', 'infix' => true, 'optional' => true],
                        ['name' => 'name', 'type' => 'string', 'infix' => true, 'optional' => true],
                        ['name' => 'language', 'type' => 'string', 'facet' => true, 'optional' => true, 'sort' => true],
                        ['name' => 'content_type', 'type' => 'string', 'infix' => true, 'facet' => true, 'optional' => true, 'sort' => true],
                        ['name' => 'institution_name_lt', 'type' => 'string', 'facet' => true, 'optional' => true, 'sort' => true],
                        ['name' => 'institution_name_en', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'tenant_shortname', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'document_date', 'type' => 'int64', 'facet' => true, 'sort' => true, 'optional' => true],
                        ['name' => 'document_year', 'type' => 'string', 'infix' => true, 'optional' => true],
                        ['name' => 'document_date_formatted', 'type' => 'string', 'optional' => true, 'infix' => true],
                        ['name' => 'is_in_effect', 'type' => 'bool', 'facet' => true, 'optional' => true],
                        ['name' => 'anonymous_url', 'type' => 'string'],
                        ['name' => 'is_active', 'type' => 'bool'],
                        ['name' => 'sync_status', 'type' => 'string', 'sort' => true, 'optional' => true],
                        ['name' => 'checked_at', 'type' => 'int64', 'optional' => true, 'sort' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title,summary,content_type,document_year,document_date_formatted',
                    'query_by_weights' => '10,3,2,6,4',
                ],
            ],

            // Public Meetings - Transparency for student representation work
            \App\Models\PublicMeeting::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string', 'infix' => true, 'sort' => true],
                        ['name' => 'description', 'type' => 'string', 'optional' => true, 'infix' => true],
                        ['name' => 'start_time', 'type' => 'int64', 'sort' => true],
                        ['name' => 'start_time_formatted', 'type' => 'string', 'optional' => true],
                        ['name' => 'year', 'type' => 'int32', 'facet' => true],
                        ['name' => 'month', 'type' => 'int32', 'facet' => true],

                        ['name' => 'institution_id', 'type' => 'string', 'optional' => true],
                        ['name' => 'institution_name_lt', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'institution_name_en', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'tenant_shortname', 'type' => 'string', 'facet' => true, 'optional' => true],

                        ['name' => 'institution_type_id', 'type' => 'int32', 'facet' => true, 'optional' => true],
                        ['name' => 'institution_type_title', 'type' => 'string', 'facet' => true, 'optional' => true],

                        ['name' => 'completion_status', 'type' => 'string', 'facet' => true],
                        ['name' => 'agenda_items_count', 'type' => 'int32', 'sort' => true],

                        ['name' => 'total_agenda_items', 'type' => 'int32'],
                        ['name' => 'items_with_decisions', 'type' => 'int32'],
                        ['name' => 'completed_items', 'type' => 'int32', 'facet' => true],
                        ['name' => 'student_success_rate', 'type' => 'int32', 'sort' => true],
                        ['name' => 'positive_outcomes', 'type' => 'int32'],
                        ['name' => 'negative_outcomes', 'type' => 'int32'],
                        ['name' => 'neutral_outcomes', 'type' => 'int32'],

                        ['name' => 'vote_matches', 'type' => 'int32'],
                        ['name' => 'vote_mismatches', 'type' => 'int32'],
                        ['name' => 'incomplete_vote_data', 'type' => 'int32'],

                        ['name' => 'has_completed_items', 'type' => 'bool', 'facet' => true],
                        ['name' => 'is_recent', 'type' => 'bool', 'facet' => true],

                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'start_time',
                ],
                'search-parameters' => [
                    'query_by' => 'title,description,institution_name_lt,institution_name_en',
                    'query_by_weights' => '10,5,3,3',
                ],
            ],

            // Public Institutions - For contacts search
            \App\Models\PublicInstitution::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string', 'infix' => true, 'sort' => true],
                        ['name' => 'name_lt', 'type' => 'string', 'infix' => true, 'sort' => true],
                        ['name' => 'name_en', 'type' => 'string', 'infix' => true, 'sort' => true, 'optional' => true],
                        ['name' => 'short_name_lt', 'type' => 'string', 'infix' => true, 'optional' => true],
                        ['name' => 'short_name_en', 'type' => 'string', 'infix' => true, 'optional' => true],
                        ['name' => 'alias', 'type' => 'string', 'infix' => true, 'optional' => true],

                        ['name' => 'email', 'type' => 'string', 'optional' => true],
                        ['name' => 'phone', 'type' => 'string', 'optional' => true],
                        ['name' => 'website', 'type' => 'string', 'optional' => true],
                        ['name' => 'address_lt', 'type' => 'string', 'optional' => true],
                        ['name' => 'address_en', 'type' => 'string', 'optional' => true],

                        ['name' => 'image_url', 'type' => 'string', 'optional' => true],
                        ['name' => 'logo_url', 'type' => 'string', 'optional' => true],
                        ['name' => 'has_logo', 'type' => 'bool', 'sort' => true],
                        ['name' => 'facebook_url', 'type' => 'string', 'optional' => true],
                        ['name' => 'instagram_url', 'type' => 'string', 'optional' => true],

                        ['name' => 'tenant_id', 'type' => 'int32', 'optional' => true],
                        ['name' => 'tenant_shortname', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'tenant_alias', 'type' => 'string', 'optional' => true],

                        ['name' => 'type_ids', 'type' => 'int32[]', 'optional' => true],
                        ['name' => 'type_slugs', 'type' => 'string[]', 'facet' => true, 'optional' => true],
                        ['name' => 'type_titles_lt', 'type' => 'string[]', 'optional' => true],
                        ['name' => 'type_titles_en', 'type' => 'string[]', 'optional' => true],

                        ['name' => 'duties_count', 'type' => 'int32', 'sort' => true],
                        ['name' => 'has_contacts', 'type' => 'bool', 'facet' => true],

                        ['name' => 'created_at', 'type' => 'int64'],
                        ['name' => 'updated_at', 'type' => 'int64', 'sort' => true],
                    ],
                    'default_sorting_field' => 'updated_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title,name_lt,name_en,short_name_lt,short_name_en,alias',
                    'query_by_weights' => '10,10,8,6,4,3',
                ],
            ],
        ],
    ],

];
