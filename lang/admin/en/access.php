<?php

return [
    'eyebrow' => 'Account',
    'title' => 'My responsibilities and duties',
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
    'baseline' => [
        'title' => 'Every member, even without a responsibility',
        'locked' => 'Every member',
        'locked_own' => 'Own – every member',
        'institutions' => 'Sees the institution list and active institutions\' public information, and their own current and related institutions in full.',
        'meetings' => 'Sees public meetings; sees and can edit their institutions\' meetings held while they served.',
        'agendaItems' => 'Sees the agenda items of public meetings and of meetings they took part in through a duty. Their agenda items and votes are created, edited and deleted by whoever served on the institution at the time of the meeting, and by whoever is granted the right to edit meetings.',
        'problems' => 'Sees every unit\'s problems.',
        'resources' => 'Sees all resources.',
        'duties' => 'Sees their current and past duties and terms.',
        'dutiables' => 'Everyone sees their own duties and terms; managing other people\'s terms goes through the duty.',
        'tasks' => 'Sees and completes tasks assigned to them.',
        'comments' => 'Comments wherever they can see the record; edits their own comment within 15 minutes of writing it and deletes it any time. Whoever can edit the record deletes other people\'s.',
        'reservations' => 'Anyone can start a reservation, sees and changes the ones they take part in, and deletes their own until it is approved.',
        'users' => 'Everyone sees and edits themselves (Profile) but cannot delete themselves.',
    ],
];
