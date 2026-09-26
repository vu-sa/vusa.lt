<?php

return [
    'eyebrow' => 'Account',
    'title' => 'My roles and duties',
    'lead' => 'Here you can see which duties and roles you hold and where they let you go. If something is missing, ask your coordinator.',
    'link' => 'My roles and duties',
    'current' => [
        'title' => 'Current duties',
        'empty' => 'you have no duties right now',
    ],
    'direct' => [
        'title' => 'Roles assigned directly',
        'empty' => 'no roles are assigned to you directly',
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
        'roles' => 'Roles: :roles',
    ],
];
