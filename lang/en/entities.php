<?php

return [
    'duty' => [
        'model' => '{1} duty|[2,*] duties',
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
];
