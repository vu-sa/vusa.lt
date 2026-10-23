<?php

return [
    'label' => 'Responsibilities',

    'entity' => [
        'singular' => 'Responsibility',
        'plural' => 'Responsibilities',
    ],

    'purposes' => [
        'student_representation' => [
            'label' => 'Student representation',
            'drives' => 'Its holders count as student representatives: they are listed in meetings, receive tasks when the institution has no secretary, get activity requests and count in the representative metrics.',
        ],
        'student_rep_coordination' => [
            'label' => 'Student representative coordination',
            'drives' => 'Representatives in VU bodies turn to its holders; they are notified about registrations and meetings, and emails are signed in their name.',
        ],
        'member_registration' => [
            'label' => 'Member registration',
            'drives' => 'Its holders receive new member registrations in their padalinys and may read them.',
        ],
    ],

    'scopes' => [
        'tenant' => 'Whole padalinys',
        'own_tenant' => 'Own padalinys',
        'institution_type' => 'Institution type',
        'institution' => 'Institution',
    ],

    'sources' => [
        'tenant' => 'Assigned to the whole padalinys',
        'institution_type' => 'Assigned to the institution type',
        'institution' => 'Assigned to this institution',
    ],

    'index' => [
        'lead' => 'A responsibility says what a duty does and grants the permissions that work needs. Give it to a duty, a duty type or a whole institution.',
        'search' => 'Search responsibilities',
        'create' => 'New responsibility',
        'purpose' => 'System purpose',
        'access_only' => 'Access',
        'sort_purpose' => 'With a purpose first',
        'sort_people' => 'Most people',
        'permissions' => ':count permission|:count permissions',
        'duties' => ':count duty|:count duties',
        'people' => ':count person|:count people',
    ],

    'form' => [
        'create' => 'New responsibility',
        'what' => 'What is it?',
        'what_description' => 'Name the work, not the person: e.g. "Communication", "Running the organisation".',
        'title_hint' => 'Shown in lists and on the duty page.',
        'title_example' => 'e.g. Communication',
        'description_hint' => 'What work this responsibility covers and who it is for.',
        'purpose_note' => 'This responsibility fulfils the system purpose ":purpose". You change that under Settings → System purposes.',
    ],

    'page' => [
        'purpose' => 'System purpose: :purpose',
        'purpose_settings' => 'Change in settings',
        'gaps' => ':count duty lacks permissions this work needs.|:count duties lack permissions this work needs.',
        'super_admin' => 'The super admin can do everything; there is nothing to change here.',
        'delete_title' => 'Delete the responsibility?',
        'delete_description' => 'It is taken from every duty it was given to, and their holders lose its permissions.',
    ],

    'tabs' => [
        'permissions' => 'Permissions',
        'grants' => 'Given to',
        'people' => 'People',
        'delegation' => 'Delegation',
    ],

    'facts' => [
        'permissions' => 'Permissions',
        'duties' => 'Duties',
        'people' => 'People now',
        'direct' => ':count directly|:count directly',
        'via_types' => ':count via a type|:count via types',
        'via_institutions' => ':count via an institution|:count via institutions',
    ],

    'matrix' => [
        'intro' => 'For each action, choose where it applies: own records, the whole padalinys or everywhere. Locked actions every member already has.',
        'search' => 'Search areas',
        'only_granted' => 'Granted only',
        'nothing_found' => 'No such areas.',
        'resource' => 'Area',
        'everyone' => 'Every member',
        'not_applicable' => 'Not applicable',
        'discard' => 'Discard',
        'changes' => ':added added, :removed removed|:added added, :removed removed',
        'legacy_title' => 'Old permissions',
        'legacy_description' => 'The system no longer checks these. They stay until an administrator removes them.',
        'abilities' => [
            'read' => 'View',
            'create' => 'Create',
            'update' => 'Edit',
            'delete' => 'Move to trash',
            'forceDelete' => 'Delete permanently',
            'delete_permanent' => 'Delete permanently',
            'delete_permanent_hint' => 'no trash – gone immediately',
        ],
        'scopes' => [
            'own' => 'Own',
            'padalinys' => 'In padalinys',
            'all' => 'Everywhere',
        ],
        'resources' => [
            'settings' => 'Settings',
        ],
    ],

    'grants' => [
        'intro' => 'Most often you give a responsibility to a duty. Given to a duty type, every duty of that type receives it; given to an institution, every duty in it does.',
        'duties' => 'Duties',
        'duties_empty' => 'Not given to any duty directly.',
        'duty_types' => 'Duty types',
        'duty_types_hint' => 'Every duty of this type receives the responsibility automatically.',
        'duty_types_empty' => 'Not given to any type.',
        'institutions' => 'Institutions',
        'institutions_hint' => 'Rare: every duty in the institution receives the responsibility, e.g. everyone in the Central office.',
        'add' => 'Give',
        'add_type' => 'Give to a type',
        'add_institution' => 'Give to an institution',
        'remove' => 'Take away',
        'remove_confirm' => 'Take the responsibility away from ":name"?',
        'remove_description' => 'Everyone who gets it only through this grant loses its permissions.',
        'holders' => ':count holder|:count holders',
        'reaches' => 'Reaches :count duty|Reaches :count duties',
        'missing' => 'This work also needs: :permissions',
        'sheet_title' => 'Give the responsibility',
        'sheet_description' => 'Choose who receives this responsibility and its permissions.',
        'to' => 'To',
        'to_duty' => 'A duty',
        'to_duty_type' => 'A type',
        'to_institution' => 'An institution',
        'duty_type' => 'Duty type',
        'institution' => 'Institution',
        'scope_hint' => 'What the duty is responsible for: a more specific grant overrides a broader one.',
        'own_tenant_hint' => "Each duty is responsible for its own institution's padalinys.",
        'institution_tenant_hint' => "The institution's duties are responsible for its padalinys.",
        'users' => 'Users',
        'users_hint' => 'The old way: the responsibility is given to the user themselves, not to a duty. It applies in every padalinys they hold a duty in, so give it to a duty instead and take it away here.',
        'users_super_admin_hint' => 'A member is made a super admin in the “Access” section of their page.',
        'applies_in' => 'Applies in:',
        'no_current_duties' => 'No current duties',
        'submit' => 'Give',
    ],

    'type_page' => [
        'manage' => 'Manage responsibilities',
    ],

    'people' => [
        'title' => 'People now',
        'description' => 'Everyone currently holding a duty with this responsibility.',
        'empty' => 'Nobody holds this responsibility right now.',
    ],

    'delegation' => [
        'title' => 'May assign duty types',
        'description' => 'Holders of this responsibility may put the checked types on duties in the duty form.',
    ],

    'duty' => [
        'title' => 'Responsibilities',
        'description' => 'What this duty does. Responsibilities also grant the permissions that work needs.',
        'empty' => 'No responsibilities',
        'add' => 'Add responsibility',
        'remove' => 'Remove',
        'remove_confirm' => 'Remove the responsibility “:name”?',
        'via_duty_type' => 'Via the type ":name"',
        'via_institution' => 'Via the institution ":name"',
    ],

    'sheet' => [
        'title' => 'New responsibility',
        'description' => 'The duty\'s holders receive the responsibility and its permissions. When it has a system purpose, say what they are responsible for.',
        'responsibility' => 'Responsibility',
        'only_yours' => 'You can only give responsibilities you hold yourself.',
        'scope' => 'For',
        'tenant' => 'Padalinys',
        'institution_type' => 'Institution type',
        'type' => 'Institution type',
        'type_placeholder' => 'Choose a type',
        'institution' => 'Institution',
        'institution_pick' => 'Choose an institution',
        'institution_change' => 'Change',
        'submit' => 'Add',
    ],

    'attention' => [
        'no_coordinator' => 'Padaliniai without a student representative coordinator',
        'no_coordinator_empty' => 'Every padalinys has a student representative coordinator',
        'no_coordinator_hint' => 'Representatives here have nobody to turn to, and registration notifications reach no one. Give the coordination responsibility to the coordinator\'s duty.',
    ],

    'spotlight' => [
        'title' => 'Roles are now responsibilities',
        'body' => 'Say here what the duty does. A responsibility grants its permissions too, so there are no separate roles any more.',
    ],

    'messages' => [
        'added' => 'Responsibility given.',
        'removed' => 'Responsibility taken away.',
        'permissions_saved' => 'Permissions saved: :added added, :removed removed.',
        'minimum_unmet' => 'Some duties now lack permissions this work needs.',
        'purpose_blocks_delete' => 'This responsibility cannot be deleted: it fulfils the system purpose ":purpose". Move the purpose under Settings → System purposes first.',
        'list_blocks_delete' => 'This responsibility cannot be deleted: the mailing lists :lists take people by it.',
    ],

    'validation' => [
        'not_grantable' => 'You cannot grant this responsibility: only its holders or members who manage responsibilities can.',
        'scope_not_allowed' => 'This responsibility cannot be assigned that way.',
        'target_out_of_reach' => 'You cannot manage this padalinys, institution or type.',
        'not_type_grantable' => 'This responsibility can’t be given to a duty type: a responsibility given to a type can’t hold “Everywhere” or “Delete permanently” permissions (:permissions). Give it to the duties one by one instead.',
        'type_granted_role' => 'Duty types receive this responsibility, so it can’t hold “Everywhere” or “Delete permanently” permissions (:permissions). Remove the type grants first, or leave these permissions out.',
        'self_lockout' => 'This would take away your own ability to manage responsibilities. Ask another administrator.',
    ],
];
