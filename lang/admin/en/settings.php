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
        'atstovavimas' => [
            'title' => 'Representation Settings',
            'description' => 'Configure which roles grant access to tenant-wide institutions in the representation dashboard.',
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
        'global_roles_title' => 'Global Tenant Visibility Roles',
        'global_roles_description' => 'Select roles that grant access to all tenants in the representation dashboard.',
        'global_roles_label' => 'Global Visibility Roles',
        'global_roles_placeholder' => 'Select roles',
        'global_roles_note' => 'Note: Super Admins always see all tenants regardless of this setting.',
        'tenant_roles_title' => 'Tenant Visibility Roles',
        'tenant_roles_description' => 'Select roles that grant access to the tenant tab only for tenants where the user holds a current duty with one of these roles.',
        'tenant_roles_label' => 'Tenant Visibility Roles',
        'tenant_roles_placeholder' => 'Select roles',
        'tenant_roles_note' => 'Users without these roles will only see institutions they are assigned to through duties.',
        'no_roles_found' => 'No roles found.',
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
        'atstovavimas' => 'Representation Settings',
        'authorization' => 'Authorization',
    ],
];
