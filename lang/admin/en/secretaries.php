<?php

return [
    'label' => 'Secretaries',

    'institution' => [
        'title' => 'Secretaries',
        'effect_warning' => 'When a cadence has secretaries, its meeting tasks go to them alone (otherwise to the representatives active at the time). Secretaries also see this institution in full along with all of its meetings and their agenda items, without becoming members or editors.',
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
        'secretary_hint' => 'You are the secretary for this institution (you are not a member of it).',
        'administered_hint' => 'You are the secretary for this institution (you are not a member of it).',
        'badge' => 'Secretary',
    ],

    'picker' => [
        'title' => 'Secretaries for :term',
        'confirm' => 'Save',
        'search' => 'Search for a person...',
    ],
];
