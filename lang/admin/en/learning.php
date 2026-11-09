<?php

return [
    'entity' => ['singular' => 'Learning step', 'plural' => 'Learning steps'],
    'tabs' => [
        'path' => 'My path',
        'library' => 'Library',
        'glossary' => 'Glossary',
        'handover' => 'Handover',
        'reps' => 'My representatives',
    ],
    'pages' => [
        'path' => 'My path',
        'library' => 'Library',
        'glossary' => 'Glossary',
        'handover' => 'Handover',
        'reps' => 'My representatives',
        'step' => 'Learning step',
        'create' => 'Create learning step',
        'edit' => 'Edit learning step',
        'rep' => 'Representative path',
    ],
    'kinds' => [
        'knowledge' => 'Knowledge',
        'action' => 'Action',
        'meeting_prep' => 'Meeting preparation',
    ],
    'layers' => [
        'platform' => 'Platform',
        'foundation' => 'Foundation',
        'institution_type' => 'Institution type',
        'duty_type' => 'Duties',
        'tenant' => 'Unit',
    ],
    'stages' => [
        'before_start' => 'Before the start',
        'first_months' => 'First months',
        'whole_term' => 'Throughout the term',
        'term_end' => 'End of term',
    ],
    'importance' => [
        'essential' => 'Essential',
        'recommended' => 'Recommended',
        'optional' => 'Optional',
    ],
    'states' => [
        'pending' => 'Pending',
        'done' => 'Done',
        'skipped' => 'Skipped',
        'updated' => 'Updated',
    ],
    'transparency' => 'Your coordinator can see your progress: :names',
    'empty' => [
        'title' => 'Your learning space will be here',
        'description' => 'We are preparing the steps, glossary and handover.',
    ],
    'validation' => [
        'stage' => 'Choose a valid stage for this step.',
        'source' => 'Choose either a document or a URL.',
        'progress' => 'This progress change is not allowed.',
    ],
    'system_steps' => [
        'check_access' => [
            'title' => 'Check your duties and access',
            'description' => 'Check your institutions, duties, dates and available sections. Report anything missing or incorrect to an administrator.',
            'action' => 'Review my responsibilities and duties',
        ],
        'review_contacts' => [
            'title' => 'Review your contact details',
            'description' => 'Account settings let you update your contacts, photo and form of address. Your login email cannot be changed here, and your name can only be corrected once. A photo and phone number are optional.',
            'action' => 'Open account settings',
        ],
        'notification_settings' => [
            'title' => 'Choose how to receive notifications',
            'description' => 'Check which email addresses receive notifications, the digest frequency and the notification types you need. You can keep the defaults. You can also send a test email from the settings page.',
            'action' => 'Review notification settings',
        ],
        'profile_photo' => [
            'title' => 'Add your photo',
            'description' => 'A photo helps colleagues recognise you. You can change it in your account.',
            'action' => 'Open account settings',
        ],
        'install_app' => [
            'title' => 'Install Mano VU SA',
            'description' => 'Add Mano VU SA to your phone’s home screen or computer’s applications for quick access. Installation is optional; you can continue using your browser.',
            'action' => 'Install',
        ],
        'browser_push' => [
            'title' => 'Enable browser notifications',
            'description' => 'Receive reminders and other notifications you choose on this device. Your browser only asks for permission after you press the enable button. Declining leaves your email and in-app notification settings unchanged.',
            'action' => 'Enable notifications',
        ],
        'orientation' => [
            'title' => 'Learn where to start',
            'description' => 'Home shows work needing your attention first. The workspace menu lists sections you can access. Help, the guide and the interactive tour are available through your account menu.',
            'action' => 'Review my tasks',
        ],
        'meet_coordinator' => [
            'title' => 'Meet your coordinator',
            'description' => 'Agree how to stay in touch and where to ask for help.',
            'action' => 'Get in touch',
        ],
        'follow_institution' => [
            'title' => 'Follow your institution',
            'description' => 'Follow your institution to receive relevant notifications.',
            'action' => 'Follow institution',
        ],
        'meet_institution_contacts' => [
            'title' => 'Meet your institution contacts',
            'description' => 'Find out who organises meetings and who can help with the agenda.',
            'action' => 'View contacts',
        ],
        'record_first_meeting' => [
            'title' => 'Record your first meeting',
            'description' => 'Record the questions discussed and decisions made after your meeting.',
            'action' => 'Record a meeting',
        ],
        'read_handover' => [
            'title' => 'Read your handover notes',
            'description' => 'Review your predecessor’s experience, unfinished work and advice.',
            'action' => 'Read notes',
        ],
        'write_handover' => [
            'title' => 'Pass on your experience',
            'description' => 'Describe unfinished work, useful contacts and advice for your successor.',
            'action' => 'Write notes',
        ],
    ],
];
