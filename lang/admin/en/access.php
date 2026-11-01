<?php

return [
    'title' => 'Responsibilities and duties',
    'lead' => 'Here you can see which duties and responsibilities you hold and where they let you go. If something is missing, ask your coordinator.',
    'link' => 'My responsibilities and duties',
    'current' => [
        'title' => 'Current duties',
        'empty' => 'you have no duties right now',
    ],
    'direct' => [
        'title' => 'Responsibilities assigned directly',
        'empty' => 'no responsibilities are assigned to you directly',
    ],
    'capabilities' => [
        'title' => 'What you can do',
        'empty' => 'you cannot open any section yet',
        'super_admin' => 'You are a system administrator: you can open every section.',
    ],
    'upcoming' => [
        'title' => 'Upcoming duties',
    ],
    'ended' => [
        'title' => 'Term history',
    ],
    'band' => [
        'started' => 'Your access changed: from :date you have the duty “:duty”.',
        'ended' => 'Your access changed: from :date you no longer have the duty “:duty”.',
        'many' => 'Your access changed: :count duty changes.',
        'view' => 'View',
        'dismiss' => 'OK',
    ],
    'history' => [
        'title' => 'Access changes',
        'empty' => 'your duties have not changed in the past year',
        'started' => 'Started the duty “:duty”',
        'ended' => 'The duty “:duty” ended',
    ],
    'term' => [
        'until_now' => 'now',
        'ex_officio' => 'Ex officio',
        'represents' => 'Representing: :tenant',
        'roles' => 'Responsibilities: :roles',
    ],
    'duty_period' => 'your time in office',
    'own_institutions' => 'Own institutions, all terms',
    'own_institution_documents' => 'Own institutions',
    'permission_scopes' => [
        'meetings' => [
            'read' => [
                'padalinys' => 'Private agenda items also need agenda reading access.',
                '*' => 'Private agenda items also need agenda reading access.',
            ],
        ],
        'agendaItems' => [
            'create' => [
                'padalinys' => 'Items can be added to visible meetings without meeting editing access.',
                '*' => 'Items can be added to visible meetings without meeting editing access.',
            ],
        ],
    ],
    'baseline' => [
        'title' => 'Every member, even without a responsibility',
        'locked' => 'Every member',
        'locked_own' => 'Own – every member',
        'institutions' => 'Sees the institution list and active institutions\' public information, and their own current, related and secretaried institutions in full.',
        'meetings' => 'You see public meetings and meetings of institutions reached through non-ended duties, authorized relationships or secretary nominations. You can see and edit your institutions’ meetings whose date falls within :period, even after your duties end.',
        'agendaItems' => 'You see public agenda items of public meetings. You can see, create, edit and delete items and votes of your institutions’ meetings dated within :period, even after your duties end. You also see items through non-ended duties, authorized relationships and secretary nominations. Meeting editing access includes managing its agenda.',
        'problems' => 'Sees every unit\'s problems.',
        'resources' => 'Sees all resources.',
        'documents' => 'Every member browses published documents in ViSAK. These permissions are for managing them: seeing pending and hidden files, publishing and hiding.',
        'duties' => 'Sees their current and past duties and terms.',
        'dutiables' => 'Everyone sees their own duties and terms; managing other people\'s terms goes through the duty.',
        'tasks' => 'Sees and completes tasks assigned to them.',
        'comments' => 'Comments wherever they can see the record; edits their own comment within 15 minutes of writing it and deletes it any time. Whoever can edit the record deletes other people\'s.',
        'reservations' => 'Anyone can start a reservation, sees and changes the ones they take part in, and deletes their own until it is approved.',
        'users' => 'Everyone sees and edits themselves (Profile) but cannot delete themselves.',
    ],
];
