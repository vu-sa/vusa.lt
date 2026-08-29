<?php

return [
    'label' => 'Administratoriai',

    'institution' => [
        'title' => 'Institucijos administratoriai',
        'description' => 'Žmonės, atsakingi už šios institucijos posėdžių tvarkymą kiekvienoje kadencijoje.',
        'effect_warning' => 'Kai kadencijai nurodyti administratoriai, tos kadencijos posėdžių užduotys tenka tik jiems — kiti nariai jų nebegauna. Jei administratorių nėra, užduotys tenka tuo metu aktyviems atstovams.',
        'none_yet' => 'Administratorių nėra',
        'current_term' => 'Dabartinė',
        'inherited_term' => 'Bendra',
        'no_cadences' => 'Kadencijų nėra',
        'no_cadences_hint' => 'Administratorius galima priskirti tik kadencijai. Pirmiausia nurodykite kadencijas aukščiau.',
    ],

    'actions' => [
        'manage' => 'Tvarkyti',
        'remove' => 'Pašalinti :name',
    ],

    'dashboard' => [
        'administered_hint' => 'Esate šios institucijos administratorius (ne narys).',
    ],

    'picker' => [
        'title' => 'Kadencijos :term administratoriai',
        'confirm' => 'Išsaugoti',
        'search' => 'Ieškoti žmogaus...',
    ],

    'spotlight' => [
        'title' => 'Naujiena: institucijos administratoriai',
        'description' => 'Nurodykite, kas atsakingas už šios institucijos posėdžius. Tada užduotys ir priminimai keliaus tik jiems, o ne visiems nariams.',
    ],
];
