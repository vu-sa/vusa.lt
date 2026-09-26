<?php

return [
    // General tour UI
    'next' => 'Next',
    'previous' => 'Previous',
    'done' => 'Done',
    'skip' => 'Skip',
    'step_of' => '{{current}} of {{total}}',

    // ViSAK overview (/mano/dashboard/atstovavimas)
    'atstovavimas_overview' => [
        'welcome' => [
            'title' => 'ViSAK overview',
            'description' => 'Your representation work in institutions at a glance: where <strong>attention is needed</strong>, what is coming up and how it has gone so far.',
        ],
        'institutions_card' => [
            'title' => 'Needs attention',
            'description' => 'Institutions with no recent meeting or activity recorded. From here you can <strong>record a meeting</strong> straight away or mark that there was none (holidays, exam session, etc.).',
        ],
        'meetings_card' => [
            'title' => 'Upcoming meetings',
            'description' => 'The nearest scheduled meetings. Tap a meeting to see its agenda and details.',
        ],
        'create_meeting' => [
            'title' => 'New meeting',
            'description' => 'Use <strong>+ Create</strong> to register a new meeting: pick the institution, date and agenda.',
        ],
        'timeline' => [
            'title' => 'Timeline',
            'description' => 'An <strong>activity timeline</strong> of your institutions – meetings, gaps and active periods. It helps you plan ahead.',
        ],
        'complete' => [
            'title' => 'All set!',
            'description' => 'Questions? Reach out to your unit coordinator. You can replay this tour any time from <strong>Help</strong>.',
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
        'welcome' => [
            'title' => 'Welcome to Mano VU SA!',
            'description' => 'A quick look at where things are: moving between workspaces, finding and creating what you need, and where your tasks wait for you. It takes less than a minute.',
        ],
        'all_sections' => [
            'title' => 'All sections',
            'description' => 'Everything you can open with your account, in one place. Handy when you are not sure which workspace something lives in.',
        ],
        'quick_actions' => [
            'title' => 'Quick actions',
            'description' => 'Create actions from the workspaces you can work in – the same as <strong>+ Create</strong>, one click closer.',
        ],
        'section_switcher' => [
            'title' => 'Where you are',
            'description' => 'Shows the workspace and section you are in. Tap it to jump to another section.',
        ],
        'command_palette_mobile' => [
            'title' => 'Search',
            'description' => 'Find pages, documents, contacts or recently edited records.',
        ],
        'mobile_menu' => [
            'title' => 'Menu',
            'description' => 'Other workspaces, your account, settings and help – you can replay this tour from here too.',
        ],
        'workspaces' => [
            'title' => 'Workspaces',
            'description' => 'Mano VU SA is organized into workspaces based on your duties and permissions (Home, ViSAK, Reservations, etc.). Switch between them here.',
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
