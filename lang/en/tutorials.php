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

    // Tenant Tab Spotlight
    'tenant_tab_spotlight' => [
        'title' => 'Unit View',
        'description' => 'As a manager, you can view the entire unit\'s institution timeline. Click here to see the overall picture.',
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
];
