<?php

/**
 * Labels for the navigation catalog (App\Services\AdminNavigation\AdminNavigationCatalog).
 * Keys are ASCII-safe (never the accented workspace/section name) so they stay stable across
 * relabelling — the label itself lives only here.
 */
return [
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
        'pagalbos_uzklausos' => 'Support requests',
        'sharepoint_failai' => 'SharePoint files',
    ],

    'actions' => [
        'new_meeting' => [
            'title' => 'New meeting',
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
