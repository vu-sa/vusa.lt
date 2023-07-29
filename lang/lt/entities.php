<?php

return [
    'duty' => [
        'model' => '{1} pareiga|[2,9] pareigos|[10,*] pareigų',
    ],
    'padalinys' => [
        'model' => '{1} padalinys|[2,9] padaliniai|[10,*] padalinių',
    ],
    'reservation' => [
        'model' => '{1} rezervacija|[2,9] rezervacijos|[10,*] rezervacijų',
        'managers' => '{1} rezervacijos valdytojas|[2,9] rezervacijos valdytojai|[10,*] rezervacijos valdytojų',
        'start_time' => 'pradžia',
        'end_time' => 'pabaiga',
        'resources' => 'rezervuoti ištekliai',
        'is_reservable' => 'ar rezervuojamas?',
        'period' => 'rezervacijos laikotarpis',
    ],
    'resource' => [
        'model' => '{1} išteklius|[2,9] ištekliai|[10,*] išteklių',
    ],
    'reservation_resource' => [
        'model' => '{1} rezervacijos išteklius|[2,9] rezervacijos ištekliai|[10,*] rezervacijos išteklių',
    ],
    'meta' => [
        'model_list' => ':model sąrašas',
        'help' => 'Kaip veikia :model?',
    ],
];
