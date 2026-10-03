<?php

return [
    'label' => 'Secretaries',

    'institution' => [
        'title' => 'Secretaries',
        'effect_warning' => 'When a cadence has secretaries, its meeting tasks go to them alone. Otherwise they go to the representatives active at the time.',
        'current_term' => 'Current',
        'next_term' => 'Next',
        'previous' => 'Previous secretaries',
        'no_cadences_hint' => 'There is no current cadence, so secretaries cannot be assigned. Add a cadence below.',
    ],

    'actions' => [
        'add' => 'Assign',
        'manage' => 'Change',
        'remove' => 'Remove :name',
    ],

    'dashboard' => [
        'administered_hint' => 'You are the secretary for this institution (you are not a member of it).',
        'badge' => 'Secretary',
    ],

    'picker' => [
        'title' => 'Secretaries for :term',
        'confirm' => 'Save',
        'search' => 'Search for a person...',
    ],
];
