<?php

return [
    'title' => 'Staging environment',
    'summary' => 'Data is refreshed every night',
    'details' => 'What is different?',
    'details_title' => 'How staging differs',
    'details_description' => 'Try anything here – real members receive nothing from this environment.',
    'collapse' => 'Collapse staging warning',
    'expand' => 'Open staging warning',
    'topics' => [
        'reset' => 'Changes do not last – data is copied from production every night.',
        'files' => 'File storage is shared with production, so files are view-only.',
        'sharepoint_read_only' => 'SharePoint is shared with production, so documents are view-only.',
        'sharepoint_test_site' => 'SharePoint documents are uploaded to the test site.',
        'mail' => 'Emails are not sent, except "Ar vyko posėdis?" – it comes only to you, one per selected recipient.',
    ],
];
