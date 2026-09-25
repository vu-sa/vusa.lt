<?php

return [
    'label' => 'Secretaries',

    'institution' => [
        'title' => 'Institution secretaries',
        'description' => 'The people responsible for this institution\'s meetings in each cadence.',
        'effect_warning' => 'When a cadence has secretaries, the meeting tasks for that cadence go to them alone — other members stop receiving them. With no secretaries, the tasks go to the representatives active at the time.',
        'none_yet' => 'No secretaries',
        'current_term' => 'Current',
        'inherited_term' => 'Shared',
        'no_cadences' => 'No cadences',
        'no_cadences_hint' => 'Secretaries are nominated per cadence. Define the cadences above first.',
    ],

    'actions' => [
        'manage' => 'Manage',
        'remove' => 'Remove :name',
    ],

    'dashboard' => [
        'administered_hint' => 'You are the secretary for this institution (you are not a member of it).',
    ],

    'picker' => [
        'title' => 'Secretaries for :term',
        'confirm' => 'Save',
        'search' => 'Search for a person...',
    ],
];
