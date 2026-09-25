<?php

return [
    // General tour UI
    'next' => 'Next',
    'previous' => 'Previous',
    'done' => 'Done',
    'skip' => 'Skip',
    'step_of' => '{{current}} of {{total}}',

    // Atstovavimas Overview Tour
    'atstovavimas_overview' => [
        'welcome' => [
            'title' => 'Welcome to Representation!',
            'description' => 'This area helps you <strong>track and manage</strong> your representation activities in institutions. Let us briefly introduce you to the main features.',
        ],
        'institutions_card' => [
            'title' => 'Your Institutions',
            'description' => 'Here you can see all institutions where you represent. The card shows which institutions <strong>need attention</strong> – whether meetings are missing or it\'s time to plan new activities.',
        ],
        'institution_item' => [
            'title' => 'Institution Row',
            'description' => 'Each institution has a <strong>status indicator</strong> and action buttons. You can schedule a meeting or report an absence (vacation, exams, etc.).',
        ],
        'meetings_card' => [
            'title' => 'Upcoming Meetings',
            'description' => 'Here you can see your <strong>nearest scheduled meetings</strong>. The number at the top shows how many meetings are waiting. Click on a meeting to view details.',
        ],
        'create_meeting' => [
            'title' => 'Create New Meeting',
            'description' => 'Use this button to <strong>create a new meeting</strong>. You can select the institution and set the date and agenda.',
        ],
        'all_meetings' => [
            'title' => 'View All Meetings',
            'description' => 'This button opens the <strong>full meeting list</strong> with advanced search and filters. Here you\'ll also find past meetings.',
        ],
        'timeline' => [
            'title' => 'Timeline',
            'description' => 'Here you can see your institutions\' <strong>activity timeline</strong> – meetings, gaps, and activity periods. This helps you visually plan your representation activities.',
        ],
        'complete' => [
            'title' => 'You\'re Ready!',
            'description' => 'Now you know the main Representation features. If you have questions, contact your unit coordinator.',
        ],
    ],

    // Gantt Chart Tour
    'gantt_tour' => [
        'fullscreen' => [
            'title' => 'Fullscreen Mode',
            'description' => 'We recommend starting with <strong>fullscreen mode</strong> – you\'ll see more information and it\'s easier to navigate.',
        ],
        'chart_overview' => [
            'title' => 'Timeline Diagram',
            'description' => 'This is a <strong>Gantt chart</strong> showing all institutions\' meeting history and upcoming meetings on a time axis. Each row represents one institution.',
        ],
        'date_navigation' => [
            'title' => 'Year Navigation',
            'description' => 'Click the date to <strong>jump to other years</strong>. You\'ll also find a button to return to today.',
        ],
        'scale' => [
            'title' => 'Scale Control',
            'description' => 'Use the slider to <strong>change the scale size</strong> – decrease to see more time, or increase for a more detailed view.',
        ],
        'filters' => [
            'title' => 'Filter Options',
            'description' => 'Click this button to <strong>open the filters</strong>. You can select units, show only active institutions, or public institutions.',
        ],
        'institution_row' => [
            'title' => 'Institution Name',
            'description' => 'The institution name is a <strong>link</strong> – click to open the institution page with all information.',
        ],
        'meeting_icons' => [
            'title' => 'Meeting Markers',
            'description' => 'The dots on the diagram mark <strong>meetings</strong>. Click on any dot to open meeting details.',
        ],
        'safety_bands' => [
            'title' => 'Periodicity Zones',
            'description' => 'Green bands show the <strong>recommended meeting frequency</strong>. If a meeting occurs within the zone – all is well. Orange lines indicate missing meetings.',
        ],
        'legend' => [
            'title' => 'Legend',
            'description' => 'We\'re done! Click here to <strong>open the legend</strong> with explanations of all diagram elements.',
        ],
    ],

    // Admin Home / Welcome Tour (≤ 5 steps)
    'admin_home' => [
        'workspaces' => [
            'title' => 'Workspaces',
            'description' => 'Mano VU SA is organized into workspaces based on your duties and permissions (Home, Representation, Reservations, etc.). Switch between them here.',
        ],
        'command_palette' => [
            'title' => 'Quick search',
            'description' => 'Press <strong>Ctrl+K</strong> (or <strong>⌘K</strong>) or click the search box to find pages, documents, contacts, or recently edited records.',
        ],
        'action_create' => [
            'title' => 'Quick create',
            'description' => 'A single door for creation – start a new meeting, request a reservation, or create a registration.',
        ],
        'tasks_card' => [
            'title' => 'Tasks & attention queue',
            'description' => 'Your personal tasks and upcoming reminders stay visible on Home – nothing gets lost.',
        ],
        'account_menu' => [
            'title' => 'Account & help',
            'description' => 'Access your profile, appearance and language settings, documentation, and the help menu here.',
        ],
    ],

    // Dutiable timeline editor (/mano/dutiables/timeline)
    'dutiable_timeline' => [
        'welcome' => [
            'title' => 'Duty periods',
            'description' => 'This is <strong>every duty period in one institution</strong> on a single timeline. Instead of editing each member on their own page, you can fix them all at once.',
        ],
        'institution' => [
            'title' => 'Institution',
            'description' => 'The institution on screen. Click to switch — the ones you hold duties in are offered first.',
        ],
        'chart' => [
            'title' => 'The timeline',
            'description' => 'Each bar is one duty period. The green bands are <strong>cadences</strong>: a bar that does not line up with a band edge is exactly what this page exists to fix. Bars can be dragged; the "i" button on the right says how.',
        ],
        'controls' => [
            'title' => 'Collapsing and sorting',
            'description' => 'Collapse every duty for an overview. Where study programmes are recorded, rows can be sorted by them.',
        ],
        'filters' => [
            'title' => 'Filters',
            'description' => 'Filter by cadence or unit. The same menu is where you choose whether <strong>ended</strong> periods are listed at all.',
        ],
        'selection' => [
            'title' => 'The selected row',
            'description' => 'Select a bar to see its exact dates and what you can do with it: align to the cadence, close it, merge or remove it.',
        ],
        'suggestions' => [
            'title' => 'Suggested fixes',
            'description' => 'Inconsistencies are found for you — overlapping periods, open-ended duties left running past a finished cadence — and can be applied in one click.',
        ],
        'save' => [
            'title' => 'Preview and save',
            'description' => 'Nothing is written until you save, and you can <strong>preview</strong> exactly how the records will look first.',
        ],
    ],
];
