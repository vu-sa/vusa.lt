<?php

return [
    'duty' => [
        'model' => '{1} duty|[2,*] duties',
    ],
    'document' => [
        'model' => '{1} document|[2,*] documents',
    ],
    'user' => [
        'model' => '{1} member|[2,*] members',
    ],
    'news' => [
        'model' => '{1} news item|[2,*] news items',
    ],
    'page' => [
        'model' => '{1} page|[2,*] pages',
    ],
    'banner' => [
        'model' => '{1} banner|[2,*] banners',
    ],
    'category' => [
        'model' => '{1} category|[2,*] categories',
    ],
    'tag' => [
        'model' => '{1} tag|[2,*] tags',
    ],
    'type' => [
        'model' => '{1} content type|[2,*] content types',
    ],
    'relationship' => [
        'model' => '{1} relationship|[2,*] relationships',
    ],
    'calendar' => [
        'model' => '{1} event|[2,*] events',
    ],
    'form' => [
        'model' => '{1} form|[2,*] forms',
    ],
    'role' => [
        'model' => '{1} role|[2,*] roles',
    ],
    'permission' => [
        'model' => '{1} permission|[2,*] permissions',
    ],
    'studyProgram' => [
        'model' => '{1} study programme|[2,*] study programmes',
    ],
    'studySet' => [
        'model' => '{1} individual study set|[2,*] individual study sets',
    ],
    'institution' => [
        'model' => '{1} institution|[2,*] institutions',
    ],
    'meeting' => [
        'model' => '{1} meeting|[2,*] meetings',
    ],
    'tenant' => [
        'model' => '{1} unit|[2,*] units',
    ],
    'training' => [
        'model' => '{1} training|[2,*] trainings',
    ],
    'reservation' => [
        'model' => '{1} reservation|[2,*] reservations',
        'managers' => '{1} reservation manager|[2,*] reservation managers',
        'start_time' => 'start time',
        'end_time' => 'end time',
        'resources' => 'reserved resources',
        'is_reservable' => 'is reservable?',
        'period' => 'reservation period',
    ],
    'resource' => [
        'model' => '{1} resource|[2,*] resources',
    ],
    'resource_category' => [
        'model' => '{1} resource category|[2,*] resource categories',
    ],
    'reservation_resource' => [
        'model' => '{1} reservation resource|[2,*] reservation resources',
    ],
    'meta' => [
        'model_list' => ':model list',
        'help' => 'How :model work?',
    ],
    'membership' => [
        'model' => '{1} membership|[2,*] memberships',
    ],
    'problem' => [
        'model' => '{1} problem|[2,*] problems',
        'title' => 'problem title',
        'description' => 'problem description',
        'solution' => 'solution',
        'steps_taken' => 'Steps taken',
        'occurred_at' => 'occurred date',
        'resolved_at' => 'resolved date',
        'status' => 'status',
        'responsible_user' => 'responsible person',
        'categories' => 'categories',
    ],
];
