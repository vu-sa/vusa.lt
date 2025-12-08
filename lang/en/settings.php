<?php

return [
    // Settings index page
    'title' => 'Settings',
    'description' => 'Manage system settings and configurations.',

    // Settings categories
    'categories' => [
        'general' => 'General Settings',
        'authorization' => 'Authorization Settings',
    ],

    // Settings pages
    'pages' => [
        'forms' => [
            'title' => 'Form Settings',
            'description' => 'Configure form-related settings like member registration.',
        ],
        'meetings' => [
            'title' => 'Meeting Display Settings',
            'description' => 'Configure which institution types have publicly visible meetings.',
        ],
        'authorization' => [
            'title' => 'Settings Authorization',
            'description' => 'Configure which role can manage system settings.',
        ],
    ],

    // Form labels and descriptions
    'authorization_form' => [
        'role_label' => 'Settings Manager Role',
        'role_description' => 'Select which role can manage settings. If not selected, only Super Admins can manage settings.',
        'role_placeholder' => 'Only Super Admins (default)',
        'super_admin_note' => 'Note: Super Admins can always manage settings regardless of this setting.',
    ],

    // Form settings page
    'form_settings' => [
        'registration_form_title' => 'Member Registration Form',
        'registration_form_description' => 'Select which registration form from the database will be used for member registration. If the registration form has a unit field, emails will be automatically sent to registrants and also to people who have the designated role.',
        'form_label' => 'Form',
        'form_placeholder' => 'Select a form',
        'role_label' => 'Notification Role',
        'role_placeholder' => 'Select a role',
    ],

    // Meeting settings page
    'meeting_settings' => [
        'types_title' => 'Institution Types with Public Meetings',
        'types_description' => 'Select which institution types will have their meetings displayed publicly on contact pages. For example: study board, QAP council, study program committee.',
        'types_label' => 'Institution Types',
        'types_placeholder' => 'Select institution types',
        'no_types_found' => 'No institution types found.',
        'excluded_types_title' => 'Institution Types Without Meetings',
        'excluded_types_description' => 'Select institution types that should be hidden from the representation dashboard. Institutions of these types (e.g., unit, PKP) do not have formal meetings and should not be tracked.',
        'excluded_types_label' => 'Excluded Institution Types',
        'excluded_types_placeholder' => 'Select institution types to exclude',
    ],

    // Messages
    'messages' => [
        'updated' => 'Settings updated successfully.',
        'authorization_updated' => 'Settings authorization updated successfully.',
        'unauthorized' => 'You are not authorized to manage settings.',
    ],

    // Breadcrumbs
    'breadcrumbs' => [
        'index' => 'Settings',
        'forms' => 'Form Settings',
        'meetings' => 'Meeting Settings',
        'authorization' => 'Authorization',
    ],
];
