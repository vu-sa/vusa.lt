<?php

return [
    // Settings index page
    'title' => 'Settings',
    'description' => 'Manage system settings and configurations.',

    // Settings categories
    'categories' => [
        'general' => 'General Settings',
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
            'description' => 'Configure which institution types count as student representative bodies.',
        ],
        'cadences' => [
            'title' => 'Cadences',
            'description' => 'Set the term start and end dates, and any institution overrides.',
        ],
        'site' => [
            'title' => 'Site settings',
            'description' => 'Point the system at the pages it links to, such as the privacy policy.',
        ],
        'purposes' => [
            'title' => 'System purposes',
            'description' => 'Choose which responsibility and which duty types fulfil the parts the system relies on.',
        ],
    ],

    // Form settings page
    'form_settings' => [
        'registration_form_title' => 'Member Registration Form',
        'registration_form_description' => 'Select which registration form from the database will be used for member registration. If the registration form has a unit field, emails will be automatically sent to registrants and to the padalinys members who hold the member registration responsibility.',
        'form_label' => 'Form',
        'form_placeholder' => 'Select a form',
        'recipients_title' => 'Who receives registrations',
        'recipients_description' => 'Holders of the "Member registration" responsibility receive and see registrations in their padalinys.',
        'recipients_link' => 'Open the responsibility',
        'recipients_missing' => 'No responsibility fulfils the member registration purpose yet. Choose one under "System purposes".',
        'recipients_gaps' => 'Nobody receives registrations in these padaliniai: :tenants.',
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
        'baseline_title' => 'Representative metrics comparison',
        'baseline_label' => 'Comparison term',
        'baseline_hint' => 'Metrics use the shared organisation-wide term dates. The selected term is measured in full and the current term through today. Institution-specific term overrides do not change this comparison.',
        'baseline_automatic' => 'Automatic – previous completed term',
        'student_rep_type_title' => 'Student representative body root type',
        'student_rep_type_description' => 'Select the root institution type that (along with all its descendant types) is recognized as a student representative body. Institutions of this type will be displayed using the student rep card instead of the standard institution card in public contacts.',
        'student_rep_type_label' => 'Root type',
        'student_rep_type_placeholder' => 'Default: Student representative body',
        'student_rep_type_default' => 'Default (studentu-atstovu-organas)',
        'student_rep_type_note' => 'By default, the type with slug "studentu-atstovu-organas" is used.',
    ],

    // Document settings page
    'document_settings' => [
        'important_types_title' => 'Most Important Document Types',
        'important_types_description' => 'Choose types to show first in the filter and above equally relevant search results.',
        'important_types_label' => 'Document Types',
        'important_types_placeholder' => 'Select document types',
        'no_types_found' => 'No document types found.',
        'recommendations_title' => 'Recommended documents',
        'recommendations_description' => 'A recommendation appears above the search results when every searched word is in one of its phrases, e.g. “statut” matches “VU SA statutes”. Separate phrases with commas; word forms are recognised automatically.',
        'choose_document' => 'Choose a document',
        'move_up' => 'Move up',
        'move_down' => 'Move down',
        'remove' => 'Remove',
        'phrases_label' => 'Show when searching',
        'add_recommendation' => 'Add a recommendation',
        'when_empty' => 'Also show when the search is empty',
        'pause' => 'Turn off for now',
        'phrases_placeholder' => 'comma-separated phrases, e.g. statutes, VU SA statutes',
        'change_document' => 'change document',
        'phrases_required' => 'Enter at least one phrase or tick “Also show when the search is empty”.',
    ],

    // System purposes page
    'purposes' => [
        'intro' => 'The system uses some responsibilities and duty types for more than permissions or display: it finds people and groups public contacts by them.',
        'responsibilities_title' => 'Responsibility purposes',
        'responsibilities_description' => 'The system finds people through these responsibilities. Other responsibilities only grant permissions.',
        'responsibility_label' => 'Responsibility',
        'responsibility_none' => 'Not assigned',
        'duty_types_title' => 'Public contacts',
        'duty_types_description' => 'Padalinys contact pages are grouped by these duty types. The links do not change.',
        'duty_type_label' => 'Duty type',
        'fallback' => 'Not chosen – the type with the link ":slug" is used',
        'duty_types' => [
            'coordinator' => [
                'label' => 'Coordinators',
                'drives' => 'The /kontaktai/koordinatoriai page and where coordinators sit in a padalinys contact list.',
            ],
            'curator' => [
                'label' => 'Curators',
                'drives' => 'The /kontaktai/kuratoriai page and the study programme field when you assign a member to a curator duty.',
            ],
            'mentor' => [
                'label' => 'Mentors',
                'drives' => 'The English /contacts/mentors page and the study programme field when you assign a member to a mentor duty.',
            ],
            'delete_blocked' => 'This type cannot be deleted: it is used as :parts (Settings → System purposes).',
        ],
        'one_each' => 'A responsibility can fulfil only one purpose.',
        'curator_not_mentor' => 'Choose different types for curators and mentors.',
    ],

    // Messages
    'messages' => [
        'updated' => 'Settings updated successfully.',
        'unauthorized' => 'You are not authorized to manage settings.',
    ],

    // Breadcrumbs
    'breadcrumbs' => [
        'index' => 'Settings',
        'forms' => 'Form Settings',
        'meetings' => 'Meeting Settings',
        'documents' => 'Document Settings',
        'atstovavimas' => 'Representation Settings',
        'purposes' => 'System purposes',
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
