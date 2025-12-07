<?php

return [
    // Settings index page
    'title' => 'Nustatymai',
    'description' => 'Valdykite sistemos nustatymus ir konfigūracijas.',

    // Settings categories
    'categories' => [
        'general' => 'Bendri nustatymai',
        'authorization' => 'Autorizacijos nustatymai',
    ],

    // Settings pages
    'pages' => [
        'forms' => [
            'title' => 'Formų nustatymai',
            'description' => 'Konfigūruokite formų nustatymus, pvz., narystės registraciją.',
        ],
        'meetings' => [
            'title' => 'Posėdžių rodymo nustatymai',
            'description' => 'Konfigūruokite, kurių institucijų tipų posėdžiai rodomi viešai.',
        ],
        'authorization' => [
            'title' => 'Nustatymų autorizacija',
            'description' => 'Konfigūruokite, kuri rolė gali valdyti sistemos nustatymus.',
        ],
    ],

    // Form labels and descriptions
    'authorization_form' => [
        'role_label' => 'Nustatymų valdymo rolė',
        'role_description' => 'Pasirinkite, kuri rolė gali valdyti nustatymus. Jei nepasirinkta, tik Super Administratoriai gali valdyti nustatymus.',
        'role_placeholder' => 'Tik Super Administratoriai (numatytasis)',
        'super_admin_note' => 'Pastaba: Super Administratoriai visada gali valdyti nustatymus, nepaisant šio nustatymo.',
    ],

    // Form settings page
    'form_settings' => [
        'registration_form_title' => 'Narių registracijos forma',
        'registration_form_description' => 'Pasirinkti, kuri registracijos forma iš duombazės bus naudojama narių registracijai. Jeigu registracijos forma turi padalinio laukelį, automatiškai bus siunčiami laiškai užsiregistravusiems ir taip pat žmonėms, kurie turi numatytą rolę.',
        'form_label' => 'Forma',
        'form_placeholder' => 'Pasirinkti formą',
        'role_label' => 'Pranešimo rolė',
        'role_placeholder' => 'Pasirinkti rolę',
    ],

    // Meeting settings page
    'meeting_settings' => [
        'types_title' => 'Institucijų tipai su viešais posėdžiais',
        'types_description' => 'Pasirinkite, kurių institucijų tipų posėdžiai bus rodomi viešai kontaktų puslapiuose. Pavyzdžiui: studijų kolegija, KAP taryba, studijų programų komitetas.',
        'types_label' => 'Institucijų tipai',
        'types_placeholder' => 'Pasirinkti institucijų tipus',
        'no_types_found' => 'Institucijų tipų nerasta.',
    ],

    // Messages
    'messages' => [
        'updated' => 'Nustatymai atnaujinti sėkmingai.',
        'authorization_updated' => 'Nustatymų autorizacija atnaujinta sėkmingai.',
        'unauthorized' => 'Jūs neturite teisių valdyti nustatymus.',
    ],

    // Breadcrumbs
    'breadcrumbs' => [
        'index' => 'Nustatymai',
        'forms' => 'Formų nustatymai',
        'meetings' => 'Posėdžių nustatymai',
        'authorization' => 'Autorizacija',
    ],
];
