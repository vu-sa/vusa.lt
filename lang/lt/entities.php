<?php

return [
    'duty' => [
        'model' => '{1} pareiga|[2,9] pareigos|[10,*] pareigų',
    ],
    'meeting' => [
        'model' => '{1} susitikimas|[2,9] susitikimai|[10,*] susitikimų',
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
    'resource_category' => [
        'model' => '{1} išteklių kategorija|[2,9] išteklių kategorijos|[10,19] išteklių kategorijų',
    ],
    'reservation_resource' => [
        'model' => '{1} rezervacijos išteklius|[2,9] rezervacijos ištekliai|[10,*] rezervacijos išteklių',
    ],
    'meta' => [
        'model_list' => ':model sąrašas',
        'help' => 'Kaip veikia :model?',
    ],
];
