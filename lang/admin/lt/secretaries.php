<?php

return [
    'label' => 'Sekretoriai',

    'institution' => [
        'title' => 'Sekretoriai',
        'effect_warning' => 'Kai kadencijai priskirti sekretoriai, jos posėdžių užduotys tenka tik jiems (kitu atveju – tuo metu aktyviems atstovams). Sekretoriai taip pat pilnai mato šią instituciją bei visus jos posėdžius ir jų klausimus, tačiau nariais ar redaktoriais netampa.',
        'current_term' => 'Dabartinė',
        'next_term' => 'Kita',
        'previous' => 'Ankstesni sekretoriai',
        'no_cadences_hint' => 'Dabartinės kadencijos nėra, todėl sekretorių priskirti negalima. Pridėk kadenciją žemiau.',
    ],

    'actions' => [
        'add' => 'Priskirti',
        'manage' => 'Keisti',
        'remove' => 'Pašalinti :name',
    ],

    'dashboard' => [
        'secretary_hint' => 'Esate šios institucijos sekretorius (ne narys).',
        'administered_hint' => 'Esate šios institucijos sekretorius (ne narys).',
        'badge' => 'Sekretorius',
    ],

    'picker' => [
        'title' => 'Kadencijos :term sekretoriai',
        'confirm' => 'Išsaugoti',
        'search' => 'Ieškoti žmogaus...',
    ],
];
