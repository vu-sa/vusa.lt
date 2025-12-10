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

    // Admin Home Tour
    'admin_home' => [
        'welcome' => [
            'title' => 'Welcome to Mano VU SA!',
            'description' => 'This is your <strong>main dashboard</strong> where you\'ll find the most important tools and information. Let us briefly introduce you to the main features.',
        ],
        'meetings_card' => [
            'title' => 'Upcoming Meetings',
            'description' => 'This card shows your <strong>nearest scheduled meetings</strong>. You can quickly create a new meeting or view all of them.',
        ],
        'nav_visak' => [
            'title' => 'ViSAK – Representation',
            'description' => 'This button opens <strong>ViSAK</strong> – the Virtual Student Representatives Coordinator page where you can manage all your representation activities.',
        ],
        'nav_administravimas' => [
            'title' => 'Administration',
            'description' => 'This button opens the <strong>administration section</strong> where you can manage users, units, institutions, and other settings.',
        ],
        'quick_actions' => [
            'title' => 'Quick Actions',
            'description' => 'In this section you\'ll find <strong>quick actions</strong> – create a meeting, news, or reservation with a single click.',
        ],
        'tasks_card' => [
            'title' => 'Tasks',
            'description' => 'This card shows your <strong>personal tasks</strong> and their status. You\'ll see how many tasks are pending and which are due soon.',
        ],
        'tasks_indicator' => [
            'title' => 'Tasks Indicator',
            'description' => 'This button shows your <strong>pending tasks count</strong>. Click to review tasks or mark them as complete.',
        ],
        'notifications_indicator' => [
            'title' => 'Notifications',
            'description' => 'This button shows your <strong>unread notifications</strong>. Click to view the latest notifications.',
        ],
        'help_button' => [
            'title' => 'Help Button',
            'description' => 'This button allows you to <strong>restart this guide</strong> at any time. Each page with a guide has this button.',
        ],
        'nav_dokumentacija' => [
            'title' => 'Documentation',
            'description' => 'Click here to open the <strong>detailed platform documentation</strong> with all explanations and instructions.',
        ],
        'user_menu' => [
            'title' => 'Your Profile',
            'description' => 'In this menu you\'ll find <strong>profile settings</strong>, language and theme options, and the logout button.',
        ],
        'nav_feedback' => [
            'title' => 'Leave Feedback',
            'description' => 'Have observations or suggestions? <strong>Write to us!</strong> We constantly improve the platform based on your feedback. Good luck! ✨',
        ],
    ],
];
