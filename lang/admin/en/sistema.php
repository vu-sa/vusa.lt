<?php

return [
    'overview' => [
        'title' => 'Overview',
        'lead' => 'What whoever runs the platform should look at first.',
        'new_requests' => 'New support requests',
        'new_requests_empty' => 'no new requests',
        'status' => 'System status',
        'status_ok' => 'everything is running',
        'status_problem' => ':check — :status',
        'status_link' => 'Full status',
        'checks' => [
            'redis' => 'Redis',
            'database' => 'Database',
            'cache' => 'Cache',
            'typesense' => 'Search',
            'scheduler' => 'Scheduler',
            'digest' => 'Digest emails',
            'mail' => 'Mail',
        ],
        'statuses' => [
            'warning' => 'warning',
            'error' => 'error',
        ],
        'numbers' => [
            'open_requests' => 'Unresolved requests',
            'queued_mail' => 'Emails in the queue',
            'roles' => 'Roles',
            'users' => 'Users',
            'future_duty_holders' => 'People with upcoming duties',
        ],
    ],
    'maintenance' => [
        'title' => 'Maintenance',
        'lead' => 'Actions that would otherwise need a server shell. Each one is recorded in the activity log.',
        'run' => 'Run',
        'queued_hint' => 'Runs in the background',
        'disruptive_hint' => 'Briefly affects users',
        'confirm_title' => 'Run “:action”?',
        'actions' => [
            'refresh-public-content' => [
                'label' => 'Refresh public content cache',
                'description' => 'Clears the cache for news, pages, menus, banners, the calendar and other public data. Use it when changes do not show on the site.',
            ],
            'clear-application-cache' => [
                'label' => 'Clear the whole cache',
                'description' => 'Clears the entire application cache. Sessions survive, but the first pages load slower for a while.',
            ],
            'restart-queue-workers' => [
                'label' => 'Restart queue workers',
                'description' => 'Tells background workers to finish their current job and restart. Use it when jobs are stuck.',
            ],
            'send-test-mail' => [
                'label' => 'Send a test email',
                'description' => 'Sends an email to your address to check the mail settings.',
            ],
            'sync-public-search' => [
                'label' => 'Sync public search',
                'description' => 'Updates news, pages and contacts in public search to match their current status.',
            ],
            'refresh-institution-activity' => [
                'label' => 'Refresh institution activity',
                'description' => 'Recalculates institution activity status in admin search.',
            ],
            'sync-sharepoint-documents' => [
                'label' => 'Sync SharePoint documents',
                'description' => 'Refreshes stale documents from SharePoint (up to 50 at a time).',
            ],
            'reindex-search' => [
                'label' => 'Reindex all search',
                'description' => 'Recreates every Typesense collection. For about a minute, search may return incomplete results.',
            ],
        ],
    ],
];
