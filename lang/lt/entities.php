<?php

return [
    'duty' => [
        'model' => '{1} pareiga|[2,9] pareigos|[10,*] pareigų',
    ],
    'reservation' => [
        'model' => '{1} rezervacija|[2,9] rezervacijos|[10,*] rezervacijų',
        'managers' => '{1} rezervacijos valdytojas|[2,9] rezervacijos valdytojai|[10,*] rezervacijos valdytojų',
        'start_time' => 'pradžia',
        'end_time' => 'pabaiga',
        'resources' => 'rezervuoti ištekliai',
    ],
    'resource' => [
        'model' => '{1} išteklius|[2,9] ištekliai|[10,*] išteklių',
    ],
];
