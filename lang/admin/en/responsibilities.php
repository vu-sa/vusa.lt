<?php

return [
    'label' => 'Responsibilities',

    'types' => [
        'student_rep_coordination' => [
            'label' => 'Student representative coordination',
            'description' => 'Representatives in VU bodies turn to this duty\'s holders; they are notified about registrations and meetings, and emails are signed in their name.',
        ],
    ],

    'scopes' => [
        'tenant' => 'Whole padalinys',
        'institution_type' => 'Institution type',
        'institution' => 'Institution',
    ],

    'sources' => [
        'tenant' => 'Assigned to the whole padalinys',
        'institution_type' => 'Assigned to the institution type',
        'institution' => 'Assigned to this institution',
    ],

    'duty' => [
        'title' => 'Responsibilities',
        'description' => 'What this duty must handle. What it may do comes from its roles.',
        'empty' => 'No responsibilities',
        'roles_title' => 'Roles',
        'roles_description' => 'What this duty may do.',
        'roles_empty' => 'No roles',
        'add' => 'Add responsibility',
        'remove' => 'Remove',
        'remove_confirm' => 'Remove the responsibility “:name”?',
    ],

    'sheet' => [
        'title' => 'New responsibility',
        'description' => 'The duty\'s holders become responsible for the chosen padalinys, institution type or institution. A more specific assignment overrides a broader one.',
        'responsibility' => 'Responsibility',
        'scope' => 'For',
        'tenant' => 'Padalinys',
        'institution_type' => 'Institution type',
        'type_placeholder' => 'Choose a type',
        'institution' => 'Institution',
        'institution_pick' => 'Choose an institution',
        'institution_change' => 'Change',
        'submit' => 'Add',
    ],

    'attention' => [
        'no_coordinator' => 'Padaliniai without a student representative coordinator',
        'no_coordinator_empty' => 'Every padalinys has a student representative coordinator',
        'no_coordinator_hint' => 'Representatives here have nobody to turn to, and registration notifications reach no one. Assign the responsibility to the coordinator\'s duty.',
    ],

    'spotlight' => [
        'title' => 'New: responsibilities',
        'body' => 'Say here what the duty must handle, e.g. coordinating the padalinys\' student representatives. Roles still decide what it may do.',
    ],

    'messages' => [
        'added' => 'Responsibility added.',
        'removed' => 'Responsibility removed.',
    ],

    'validation' => [
        'scope_not_allowed' => 'This responsibility cannot be assigned that way.',
        'target_out_of_reach' => 'You cannot manage this padalinys, institution or type.',
    ],
];
