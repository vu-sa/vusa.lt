<?php

return [
    'title' => 'Cadences',
    'description' => 'The term start and end dates duty periods are managed against.',

    'defaults' => [
        'title' => 'Default dates',
        'description' => 'A new cadence is prefilled from these dates. They can always be changed by hand.',
        'start_month_day' => 'Start month and day',
        'end_month_day' => 'End month and day',
        'preview' => 'Example',
    ],

    'global' => [
        'title' => 'Shared cadences',
        'description' => 'These apply to every institution without cadences of its own.',
        'empty' => 'No cadences yet.',
    ],

    'overrides' => [
        'title' => 'Institution exceptions',
        'description' => 'An institution with at least one cadence of its own stops using the shared ones. Exceptions are managed on the institution itself.',
        'empty' => 'No exceptions.',
        'count' => ':count cadences',
        'open' => 'Open the institution',
    ],

    'institution' => [
        'title' => 'Cadences',
        'description' => 'The dates this institution’s duty periods are aligned to.',
        'override_active' => 'No longer applied',
        'inherited' => 'Inherited shared cadences',
        'inherited_hint' => 'These apply for as long as the institution has no cadence of its own.',
        'own' => 'This institution’s cadences',
        'override_warning' => 'Adding even one cadence of its own stops this institution using the shared ones entirely — including for years it has not described itself. Rarely needed.',
        'timeline' => 'Manage periods',
    ],

    'fields' => [
        'start_date' => 'Start',
        'end_date' => 'End',
        'institution' => 'Institution',
        'anchor_untitled' => 'Untitled meeting',
        'anchor_hint' => 'Pick any meeting you have access to — including another institution’s. The boundary is taken from its date and keeps in step with it.',
    ],

    'actions' => [
        'add' => 'Add a cadence',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'link_meeting' => 'Link to a meeting',
        'unlink_meeting' => 'Unlink from the meeting',
    ],

    'validation' => [
        'anchor_not_allowed' => 'That meeting cannot be used as a boundary.',
    ],

    'delete' => [
        'title' => 'Delete this cadence?',
        'description' => 'The :label cadence will be removed. Duty periods stay as they are, but will have nothing to align against.',
        'confirm' => 'Delete',
        'cancel' => 'Cancel',
    ],
];
