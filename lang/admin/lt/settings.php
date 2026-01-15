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
        'atstovavimas' => [
            'title' => 'Atstovavimo nustatymai',
            'description' => 'Konfigūruokite, kurios rolės suteikia prieigą prie padalinio institucijų atstovavimo skydelyje.',
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
        'student_rep_title' => 'Studentų atstovų registracijos forma',
        'student_rep_description' => 'Pasirinkti, kuri forma bus naudojama studentų atstovų registracijai. Kai institucija neturi aktyvių atstovų, bus rodomas mygtukas užsiregistruoti.',
        'student_rep_form_label' => 'Studentų atstovų forma',
        'student_rep_types_label' => 'Institucijų tipai',
        'student_rep_types_description' => 'Pasirinkite, kuriuose institucijų tipuose bus rodomas registracijos mygtukas, kai nėra aktyvių atstovų.',
        'student_rep_types_placeholder' => 'Pasirinkite institucijų tipus',
        'no_types_found' => 'Tipų nerasta',
        'no_form_selected' => 'Nepasirinkta (išjungta)',
    ],

    // Meeting settings page
    'meeting_settings' => [
        'types_title' => 'Institucijų tipai su viešais posėdžiais',
        'types_description' => 'Pasirinkite, kurių institucijų tipų posėdžiai bus rodomi viešai kontaktų puslapiuose. Pavyzdžiui: studijų kolegija, KAP taryba, studijų programų komitetas.',
        'types_label' => 'Institucijų tipai',
        'types_placeholder' => 'Pasirinkti institucijų tipus',
        'no_types_found' => 'Institucijų tipų nerasta.',
        'excluded_types_title' => 'Institucijų tipai be posėdžių',
        'excluded_types_description' => 'Pasirinkite institucijų tipus, kurie neturėtų būti rodomi atstovavimo skydelyje. Šių tipų institucijos (pvz., padalinys, PKP) neturi formalių posėdžių ir neturėtų būti stebimos.',
        'excluded_types_label' => 'Išskirti institucijų tipai',
        'excluded_types_placeholder' => 'Pasirinkti institucijų tipus išskyrimui',
    ],

    // Atstovavimas settings page
    'atstovavimas_settings' => [
        'global_roles_title' => 'Globalios padalinių matomumo rolės',
        'global_roles_description' => 'Pasirinkite roles, kurios suteikia prieigą prie visų padalinių atstovavimo skydelyje.',
        'global_roles_label' => 'Globalios matomumo rolės',
        'global_roles_placeholder' => 'Pasirinkti roles',
        'global_roles_note' => 'Pastaba: Super Administratoriai visada mato visus padalinius, nepaisant šio nustatymo.',
        'tenant_roles_title' => 'Padalinio matomumo rolės',
        'tenant_roles_description' => 'Pasirinkite roles, kurios suteikia prieigą prie padalinio kortelės tik tiems padaliniams, kur naudotojas turi aktyvias pareigas su šiomis rolėmis.',
        'tenant_roles_label' => 'Padalinio matomumo rolės',
        'tenant_roles_placeholder' => 'Pasirinkti roles',
        'tenant_roles_note' => 'Naudotojai be šių rolių matys tik tas institucijas, kurioms jie priskirti per pareigas.',
        'no_roles_found' => 'Rolių nerasta.',
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
        'atstovavimas' => 'Atstovavimo nustatymai',
        'authorization' => 'Autorizacija',
    ],
];
