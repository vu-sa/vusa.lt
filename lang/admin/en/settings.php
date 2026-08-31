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
        'documents' => [
            'title' => 'Document Settings',
            'description' => 'Configure which document types appear first as most important.',
        ],
        'atstovavimas' => [
            'title' => 'Representation Settings',
            'description' => 'Configure which roles grant access to tenant-wide institutions in the representation dashboard.',
        ],
        'cadences' => [
            'title' => 'Cadences',
            'description' => 'Set the term start and end dates, and any institution overrides.',
        ],
        'site' => [
            'title' => 'Site settings',
            'description' => 'Point the system at the pages it links to, such as the privacy policy.',
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
        'student_rep_title' => 'Student Representative Registration Form',
        'student_rep_description' => 'Select which form will be used for student representative registration. When an institution has no active representatives, a registration button will be displayed.',
        'student_rep_form_label' => 'Student Representative Form',
        'student_rep_types_label' => 'Institution Types',
        'student_rep_types_description' => 'Select which institution types will show the registration button when there are no active representatives.',
        'student_rep_types_placeholder' => 'Select institution types',
        'no_types_found' => 'No types found',
        'no_form_selected' => 'Not selected (disabled)',
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

    // Atstovavimas settings page
    'atstovavimas_settings' => [
        'manager_role_title' => 'Institution manager role',
        'manager_role_description' => 'Select the role that identifies institution managers for the tenant. Users with current duties of this role in the same tenant are treated as institution managers and receive institution-related notifications, e.g. about student representative registrations.',
        'manager_role_label' => 'Manager role',
        'manager_role_placeholder' => 'Select a role',
        'manager_role_note' => 'Note: institution managers are also notified about their institutions\' meetings. That is separate from institution administrators, who are nominated per cadence on the institution itself and carry its tasks.',
    ],

    // Document settings page
    'document_settings' => [
        'important_types_title' => 'Most Important Document Types',
        'important_types_description' => 'Select which document types should appear first in the filter as "Most important".',
        'important_types_label' => 'Document Types',
        'important_types_placeholder' => 'Select document types',
        'no_types_found' => 'No document types found.',
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
        'documents' => 'Document Settings',
        'atstovavimas' => 'Representation Settings',
        'authorization' => 'Authorization',
    ],

    'site_settings' => [
        'privacy_page_title' => 'Privacy policy page',
        'privacy_page_description' => 'The pages the cookie banner links to. Pick a separate page for each language; when one language has no page, its visitors get the other language\'s link.',
        'privacy_page_label' => 'Privacy policy page',
        'privacy_page_placeholder' => 'Not set',
        'privacy_page_search_placeholder' => 'Search pages by title...',
        'privacy_page_empty' => 'No pages found.',
    ],
];
