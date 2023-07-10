<?php

return [
    'duty' => [
        'model' => '{1} duty|[2,*] duties',
    ],
    'reservation' => [
        'model' => '{1} reservation|[2,*] reservations',
        'managers' => '{1} reservation manager|[2,*] reservation managers',
        'start_time' => 'start time',
        'end_time' => 'end time',
        'resources' => 'reserved resources',
    ],
    'resource' => [
        'model' => '{1} resource|[2,*] resources',
    ],
];
