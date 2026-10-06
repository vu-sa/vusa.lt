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
    'baseline' => [
        'title' => 'Every member, even without a role',
        'locked' => 'Every member',
        'locked_own' => 'Own – every member',
        'institutions' => 'Sees the institution list and active institutions\' public information, and their own current institutions in full.',
        'meetings' => 'Sees public meetings; sees and can edit their institutions\' meetings held while they served.',
        'agendaItems' => 'Sees the agenda items of public meetings and of meetings they took part in through a duty.',
        'problems' => 'Sees every unit\'s problems.',
        'resources' => 'Sees all resources.',
        'duties' => 'Sees their current and past duties.',
        'tasks' => 'Sees and completes tasks assigned to them.',
        'comments' => 'Comments wherever they can see the record, and edits and deletes their own comments. Whoever can edit the record deletes other people\'s.',
    ],
];
