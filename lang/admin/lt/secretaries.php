<?php

return [
    'label' => 'Sekretoriai',

    'institution' => [
        'title' => 'Institucijos sekretoriai',
        'description' => 'Žmonės, atsakingi už šios institucijos posėdžių tvarkymą kiekvienoje kadencijoje.',
        'effect_warning' => 'Kai kadencijai nurodyti sekretoriai, tos kadencijos posėdžių užduotys tenka tik jiems — kiti nariai jų nebegauna. Jei sekretorių nėra, užduotys tenka tuo metu aktyviems atstovams.',
        'none_yet' => 'Sekretorių nėra',
        'current_term' => 'Dabartinė',
        'inherited_term' => 'Bendra',
        'no_cadences' => 'Kadencijų nėra',
        'no_cadences_hint' => 'Sekretorius galima priskirti tik kadencijai. Pirmiausia nurodykite kadencijas aukščiau.',
    ],

    'actions' => [
        'manage' => 'Tvarkyti',
        'remove' => 'Pašalinti :name',
    ],

    'dashboard' => [
        'administered_hint' => 'Esate šios institucijos sekretorius (ne narys).',
    ],

    'picker' => [
        'title' => 'Kadencijos :term sekretoriai',
        'confirm' => 'Išsaugoti',
        'search' => 'Ieškoti žmogaus...',
    ],

    'spotlight' => [
        'title' => 'Naujiena: institucijos sekretoriai',
        'description' => 'Nurodykite, kas atsakingas už šios institucijos posėdžius. Tada užduotys ir priminimai keliaus tik jiems, o ne visiems nariams.',
    ],
];
