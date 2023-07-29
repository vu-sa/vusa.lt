<?php

return [
    'duty' => [
        'model' => '{1} duty|[2,*] duties',
    ],
    'padalinys' => [
        'model' => '{1} unit|[2,*] units',
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
    'reservation_resource' => [
        'model' => '{1} reservation resource|[2,*] reservation resources',
    ],
    'meta' => [
        'model_list' => ':model list',
        'help' => 'How :model work?',
    ],
];
