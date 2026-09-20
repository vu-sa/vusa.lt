<?php

/**
 * Labels for the navigation catalog (App\Services\AdminNavigation\AdminNavigationCatalog).
 * Keys are ASCII-safe (never the accented workspace/section name) so they stay stable across
 * relabelling — the label itself lives only here.
 */
return [
    'merge' => [
        'redirect' => 'Record merging is now available from the list actions.',
    ],
    'workspaces' => [
        'pradzia' => [
            'title' => 'Home',
            'description' => 'Your work, tasks and notifications',
        ],
        'atstovavimas' => [
            'title' => 'ViSAK',
            'description' => 'Meetings, institutions, agendas',
        ],
        'rezervacijos' => [
            'title' => 'Reservations',
            'description' => 'Rooms, equipment, advertising',
        ],
        'svetaine' => [
            'title' => 'Website',
            'description' => 'Pages, news, events',
        ],
        'organizacija' => [
            'title' => 'Organization',
            'description' => 'Members, duties, units',
        ],
        'sistema' => [
            'title' => 'System',
            'description' => 'Roles, permissions, settings',
        ],
    ],

    'chrome' => [
        'product' => 'Mano VU SA',
        'workspaces' => 'Workspaces',
        'sections_nav' => 'Workspace pages',
        'main_nav' => 'Main navigation',
        'all_sections' => 'All sections',
        'all_sections_lead' => 'Everything you can open with this account.',
        'find_section' => 'Find a section',
        'no_sections' => 'No sections found',
        'tools' => 'Tools',
        'create' => 'Create',
        'search' => 'Search',
        'search_field' => 'Search or jump to…',
        'menu' => 'Menu',
        'close_menu' => 'Close menu',
        'account' => 'Account',
        'new_design' => 'New design (beta)',
        'breadcrumbs' => 'Page path',
        'back_to' => 'Back to :page',
    ],

    'account' => [
        'appearance' => 'Appearance', 'light' => 'Light theme', 'dark' => 'Dark theme', 'language' => 'Language: :language',
        'help' => 'Help', 'docs' => 'Documentation', 'tour' => 'Show me how it works', 'report_problem' => 'Report a problem',
        'roles' => 'My roles and duties', 'my_requests' => 'My requests', 'whats_new' => "What's new", 'start_fm' => 'Listen to START FM', 'about' => 'About',
        'listen' => 'Listen', 'pause' => 'Pause', 'close' => 'Close',
    ],

    'badges' => [
        'tasks_pending' => 'Pending tasks: :count',
        'tasks_overdue' => 'Pending tasks: :count, :overdue overdue',
    ],

    'palette' => [
        'go_to' => 'Go to',
        'create' => 'Create',
        'pinned' => 'Pinned',
        'recent' => 'Recent',
        'pin' => 'Pin',
        'unpin' => 'Unpin',
    ],

    'sections' => [
        'apzvalga' => 'Overview',
        'uzduotys' => 'Tasks',
        'pranesimai' => 'Notifications',
        'institucijos' => 'Institutions',
        'posedziai' => 'Meetings',
        'darbotvarkes_klausimai' => 'Agenda items',
        'problemos' => 'Problems',
        'pareigybiu_laikotarpiai' => 'Duty terms',
        'uzduociu_suvestine' => 'Task summary',
        'institucijos_grafas' => 'Institution graph',
        'rezervacijos' => 'Reservations',
        'istekliai' => 'Resources',
        'kategorijos' => 'Categories',
        'puslapiai' => 'Pages',
        'naujienos' => 'News',
        'kalendorius' => 'Calendar',
        'baneriai' => 'Banners',
        'navigacija' => 'Navigation',
        'greitosios_nuorodos' => 'Quick links',
        'renginiu_tipai' => 'Event types',
        'zymos' => 'Tags',
        'failai' => 'Files',
        'dokumentai' => 'Documents',
        'studiju_rinkiniai' => 'Study sets',
        'nariai' => 'Members',
        'pareigybes' => 'Duties',
        'pareigybiu_atnaujinimas' => 'Duty update',
        'padaliniai' => 'Units',
        'studiju_programos' => 'Study programs',
        'registracija_nariai' => 'Member registration',
        'registracija_atstovai' => 'Student rep registration',
        'formos' => 'Forms',
        'roles' => 'Roles',
        'leidimai' => 'Permissions',
        'tipai' => 'Types',
        'rysiai' => 'Relationships',
        'nustatymai' => 'Settings',
        'sistemos_busena' => 'System status',
        'laisku_eile' => 'Mail queue',
        'rep_metrics' => 'Representative metrics',
        'pagalbos_uzklausos' => 'Support requests',
        'sharepoint_failai' => 'SharePoint files',
    ],

    'actions' => [
        'merge' => [
            'title' => 'Merge records',
        ],
        'new_meeting' => [
            'title' => 'Record a meeting',
            'description' => 'Record a meeting and its agenda',
        ],
        'no_meeting' => [
            'title' => 'No meeting held',
            'description' => 'Mark that the institution did not meet this period',
        ],
        'complete_meeting' => [
            'title' => 'Complete a meeting',
            'description' => 'Fill in a meeting already recorded',
        ],
        'new_problem' => [
            'title' => 'New problem',
            'description' => 'Report a problem affecting students',
        ],
        'new_reservation' => [
            'title' => 'New reservation',
            'description' => 'Reserve a room or equipment',
        ],
        'new_news' => [
            'title' => 'New news article',
            'description' => 'Write a news article for the website',
        ],
        'duty_update' => [
            'title' => 'Duty update',
            'description' => 'Update members across several duties at once',
        ],
        'duty_periods' => [
            'title' => 'Duty terms',
            'description' => 'Review and manage terms',
        ],
    ],
];
