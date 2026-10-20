<?php

return [
    'lead' => 'Mailman mailing lists and who receives them. On automatically managed lists, recipients change along with duties.',
    'search' => 'Search by address or name…',
    'new' => 'New mailing list',
    'import' => 'Refresh from Mailman',
    'not_configured' => 'The Mailman connection is not configured, so the last saved data is shown.',
    'empty_title' => 'No mailing lists yet',
    'empty_description' => 'Mailing lists are imported from Mailman.',

    'modes' => [
        'unmanaged' => 'Managed by hand',
        'managed' => 'Managed automatically',
    ],

    'sync_statuses' => [
        'synced' => 'In sync',
        'needs_review' => 'Needs review',
        'failed' => 'Failed',
    ],

    'fields' => [
        'required_note' => 'Fields marked with an asterisk (*) are required; you can leave the others empty.',
        'address' => 'address',
        'local_part' => 'Address',
        'display_name' => 'Name',
        'description' => 'Description',
        'tenant' => 'Unit',
        'members' => 'Recipients',
        'last_post' => 'Last post',
        'posts_last_year' => 'Posts in a year',
        'mode' => 'Management',
        'last_synced' => 'Synced',
        'archive_policy' => 'Archive',
    ],

    'hints' => [
        'local_part' => 'Lowercase letters, numbers, dots and dashes only.',
        'tenant' => 'Who can manage this mailing list.',
    ],

    'archive_policies' => [
        'public' => 'Public',
        'private' => 'Private',
        'never' => 'Not archived',
    ],

    'filters' => [
        'quiet' => 'No posts in a year',
    ],

    'sections' => [
        'recipients' => 'Recipients',
        'sources' => 'Sources',
        'exclusions' => 'Exclusions',
        'archive' => 'Archive',
    ],

    'actions' => [
        'enable_managed' => 'Manage automatically',
        'disable_managed' => 'Return to manual',
        'sync' => 'Sync',
        'apply_review' => 'Reviewed, apply',
        'edit' => 'Edit',
        'open_in_mailman' => 'Open archive in Mailman',
        'copy_address' => 'Copy address',
        'address_copied' => 'Address copied',
        'load_preview' => 'Load differences',
        'copy_failed' => 'Could not copy the address.',
    ],

    'edit' => [
        'title' => 'Edit mailing list',
        'submit' => 'Save',
    ],

    'create' => [
        'title' => 'New mailing list',
        'description' => 'The list is created in Mailman with a private archive; you set its recipients with sources.',
        'submit' => 'Create mailing list',
    ],

    'confirm_managed' => [
        'title' => 'Manage automatically?',
        'description' => 'From now on, sources decide the recipients. To add: :add, to remove: :remove. Removed people will stop receiving posts.',
        'confirm' => 'Turn on',
    ],

    'confirm_unmanaged' => [
        'title' => 'Return to manual management?',
        'description' => 'Recipients stay as they are now but will no longer follow duties.',
        'confirm' => 'Return',
    ],

    'sources' => [
        'intro' => 'Recipients are the union of all sources minus exclusions. Only duties in force today count.',
        'add' => 'Add source',
        'remove' => 'Remove',
        'remove_confirm' => 'Remove source “:name”?',
        'empty_title' => 'No sources yet',
        'empty_description' => 'Add the duties, institutions or email addresses that should receive this list.',
        'missing' => 'Source deleted',
        'expired' => 'Expired',
        'expires' => 'Valid until :date',
        'types' => [
            'duty' => 'Duty',
            'duty_type' => 'Duty type',
            'institution' => 'Institution',
            'institution_type' => 'Institution type',
            'responsibility' => 'Responsibility',
            'mailing_list' => 'Another mailing list',
            'email' => 'Email',
        ],
        'address_modes' => [
            'holder' => 'Personal email',
            'duty_mailbox' => 'Duty mailbox',
        ],
        'address_mode_hints' => [
            'holder' => 'The assignment’s email if set, otherwise the member’s email.',
            'duty_mailbox' => 'The duty’s mailbox (e.g. atstovai@…), so the address stays when people change.',
        ],
        'tenant_scopes' => [
            'all' => 'All',
            'main' => 'Central',
            'padaliniai' => 'Units',
            'selected' => 'Selected',
        ],
        'include_subtypes' => 'Include subtypes',
        'excluded_types' => 'Except types',
        'duty_types' => 'Only duty types',
        'tenants' => 'Units',
        'authorized_tenants' => 'Your units',
        'load_failed' => 'Could not load sources. Try again.',
        'retry' => 'Try again',
    ],

    'sheet' => [
        'title' => 'New source',
        'description' => 'Choose where recipients come from.',
        'submit' => 'Add',
        'type' => 'Source',
        'target' => 'Who',
        'pick' => 'Choose…',
        'responsibility_hint' => 'Everyone holding this responsibility, whether it was given to the duty, its type or its institution.',
        'address_mode' => 'Which address',
        'tenant_scope' => 'In which units',
        'email' => 'Email',
        'display_name' => 'Name',
        'note' => 'Note',
        'note_hint' => 'Why this person is on the list, so the next person managing it understands.',
        'expires_on' => 'Valid until',
        'filters' => 'More filters',
    ],

    'exclusions' => [
        'intro' => 'These addresses are kept off the list even if a source matches them.',
        'add' => 'Add exclusion',
        'remove' => 'Remove',
        'empty' => 'No exclusions.',
        'sheet_title' => 'New exclusion',
        'submit' => 'Add',
    ],

    'recipients' => [
        'empty' => 'No recipients in the last saved roster.',
        'unmanaged_note' => 'A manually managed list shows its recipients as they are in Mailman.',
        'why' => 'Why',
        'unknown_reason' => 'Added in Mailman',
    ],

    'diff' => [
        'title' => 'Differences with Mailman',
        'in_sync' => 'Mailman’s recipients match the sources.',
        'add' => 'To add (:count)',
        'remove' => 'To remove (:count)',
        'keep' => 'Keep manually',
        'exclude' => 'Leave out',
        'needs_review' => 'The sync would remove more than half the recipients, so it waits for your review.',
        'preview_hint' => 'Review what changes before turning on automatic management. Keep extra people you need as manual entries.',
    ],

    'archive' => [
        'empty' => 'No posts in the archive yet.',
        'no_access' => 'The archive is visible to the list’s recipients and those who manage it.',
        'attachments' => 'Attachments',
        'open' => 'Open in Mailman',
        'show_more' => 'Show full message',
        'show_less' => 'Collapse',
        'importing' => 'Importing archive history: :count messages saved (last known total: :total). Yearly counts will appear when the import finishes.',
    ],

    'spotlight' => [
        'title' => 'Mailing lists live here now',
        'body' => 'See who receives each list and why, and let duties keep the recipients up to date.',
    ],

    'reasons' => [
        'manual' => 'Added manually',
        'manual_with_note' => 'Added manually: :note',
        'duty_mailbox' => 'Duty mailbox: :duty',
    ],

    'messages' => [
        'not_configured' => 'The Mailman connection is not configured.',
        'imported' => 'Refreshed from Mailman: :created new, :updated updated.',
        'synced' => 'Synced: :added added, :removed removed.',
        'sync_queued' => 'Sync queued.',
        'sync_failed' => 'Could not sync with Mailman. Try again later.',
        'preview_failed' => 'Could not load recipients from Mailman.',
        'needs_review' => 'The sync would remove many recipients, so it waits for review.',
        'unmanaged' => 'The mailing list is managed by hand again.',
        'source_added' => 'Source added.',
        'source_removed' => 'Source removed.',
        'exclusion_added' => 'Exclusion added.',
        'exclusion_removed' => 'Exclusion removed.',
        'source_forbidden' => 'Choose a source within the units you can manage.',
        'stale_preview' => 'The recipients changed or this preview expired. Reload the differences and review them again.',
        'create_failed' => 'Could not create the mailing list. Try again; if the address already exists, ask a central coordinator to refresh it from Mailman.',
        'update_failed' => 'Could not save the mailing list. Try again later.',
        'import_failed' => 'Could not refresh mailing lists from Mailman. Try again later.',
    ],
];
