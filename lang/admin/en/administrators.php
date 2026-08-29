<?php

return [
    'label' => 'Administrators',

    'institution' => [
        'title' => 'Institution administrators',
        'description' => 'The people responsible for this institution\'s meetings in each cadence.',
        'effect_warning' => 'When a cadence has administrators, the meeting tasks for that cadence go to them alone — other members stop receiving them. With no administrators, the tasks go to the representatives active at the time.',
        'none_yet' => 'No administrators',
        'current_term' => 'Current',
        'inherited_term' => 'Shared',
        'no_cadences' => 'No cadences',
        'no_cadences_hint' => 'Administrators are nominated per cadence. Define the cadences above first.',
    ],

    'actions' => [
        'manage' => 'Manage',
        'remove' => 'Remove :name',
    ],

    'dashboard' => [
        'administered_hint' => 'You administer this institution (you are not a member of it).',
    ],

    'picker' => [
        'title' => 'Administrators for :term',
        'confirm' => 'Save',
        'search' => 'Search for a person...',
    ],

    'spotlight' => [
        'title' => 'New: institution administrators',
        'description' => 'Name who looks after this institution\'s meetings. Tasks and reminders then go to them instead of the whole membership.',
    ],
];
