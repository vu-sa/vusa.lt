<?php

return [
    'label' => 'Atsakomybės',

    'types' => [
        'student_rep_coordination' => [
            'label' => 'Studentų atstovų koordinavimas',
            'description' => 'Į šios pareigybės narius kreipiasi VU organų studentų atstovai; jie gauna pranešimus apie registracijas ir posėdžius, jų vardu pasirašomi laiškai.',
        ],
    ],

    'scopes' => [
        'tenant' => 'Visas padalinys',
        'type' => 'Institucijų tipas',
        'institution' => 'Institucija',
    ],

    'sources' => [
        'tenant' => 'Priskirta visam padaliniui',
        'type' => 'Priskirta institucijos tipui',
        'institution' => 'Priskirta šiai institucijai',
    ],

    'duty' => [
        'title' => 'Atsakomybės',
        'description' => 'Ką ši pareigybė turi tvarkyti. Ką ji gali daryti, lemia rolės.',
        'empty' => 'Atsakomybių nėra',
        'roles_title' => 'Rolės',
        'roles_description' => 'Ką ši pareigybė gali daryti.',
        'roles_empty' => 'Rolių nėra',
        'add' => 'Pridėti atsakomybę',
        'remove' => 'Pašalinti',
        'remove_confirm' => 'Pašalinti atsakomybę „:name“?',
    ],

    'sheet' => [
        'title' => 'Nauja atsakomybė',
        'description' => 'Pareigybės nariai bus atsakingi už pasirinktą padalinį, institucijų tipą arba instituciją. Konkretesnis priskyrimas nusveria bendresnį.',
        'responsibility' => 'Atsakomybė',
        'scope' => 'Kam',
        'tenant' => 'Padalinys',
        'type' => 'Institucijų tipas',
        'type_placeholder' => 'Pasirinkti tipą',
        'institution' => 'Institucija',
        'institution_pick' => 'Pasirinkti instituciją',
        'institution_change' => 'Keisti',
        'submit' => 'Pridėti',
    ],

    'attention' => [
        'no_coordinator' => 'Padaliniai be studentų atstovų koordinatoriaus',
        'no_coordinator_empty' => 'Visi padaliniai turi studentų atstovų koordinatorių',
        'no_coordinator_hint' => 'Studentų atstovai čia neturi į ką kreiptis, o pranešimai apie registracijas niekam nesiunčiami. Priskirk atsakomybę koordinatoriaus pareigybei.',
    ],

    'spotlight' => [
        'title' => 'Naujiena: atsakomybės',
        'body' => 'Čia nurodai, ką pareigybė turi tvarkyti, pvz., koordinuoti padalinio studentų atstovus. Rolės ir toliau lemia, ką ji gali daryti.',
    ],

    'messages' => [
        'added' => 'Atsakomybė pridėta.',
        'removed' => 'Atsakomybė pašalinta.',
    ],

    'validation' => [
        'scope_not_allowed' => 'Šiai atsakomybei toks priskyrimas negalimas.',
        'target_out_of_reach' => 'Šio padalinio, institucijos ar tipo tvarkyti negali.',
    ],
];
