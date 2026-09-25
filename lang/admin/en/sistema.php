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
];
