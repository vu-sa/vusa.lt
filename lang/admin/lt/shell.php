<?php

/**
 * Labels for the navigation catalog (App\Services\AdminNavigation\AdminNavigationCatalog).
 * Keys are ASCII-safe (never the accented workspace/section name) so they stay stable across
 * relabelling — the label itself lives only here.
 */
return [
    'workspaces' => [
        'pradzia' => [
            'title' => 'Pradžia',
            'description' => 'Tavo darbai, užduotys ir pranešimai',
        ],
        'atstovavimas' => [
            'title' => 'ViSAK',
            'description' => 'Posėdžiai, institucijos, darbotvarkės',
        ],
        'rezervacijos' => [
            'title' => 'Rezervacijos',
            'description' => 'Patalpos, įranga, reklama',
        ],
        'svetaine' => [
            'title' => 'Svetainė',
            'description' => 'Puslapiai, naujienos, renginiai',
        ],
        'organizacija' => [
            'title' => 'Organizacija',
            'description' => 'Nariai, pareigybės, padaliniai',
        ],
        'sistema' => [
            'title' => 'Sistema',
            'description' => 'Rolės, leidimai, nustatymai',
        ],
    ],

    'sections' => [
        'apzvalga' => 'Apžvalga',
        'uzduotys' => 'Užduotys',
        'pranesimai' => 'Pranešimai',
        'institucijos' => 'Institucijos',
        'posedziai' => 'Posėdžiai',
        'darbotvarkes_klausimai' => 'Darbotvarkės klausimai',
        'problemos' => 'Problemos',
        'pareigybiu_laikotarpiai' => 'Pareigybių laikotarpiai',
        'uzduociu_suvestine' => 'Užduočių suvestinė',
        'institucijos_grafas' => 'Institucijų grafas',
        'rezervacijos' => 'Rezervacijos',
        'istekliai' => 'Ištekliai',
        'kategorijos' => 'Kategorijos',
        'puslapiai' => 'Puslapiai',
        'naujienos' => 'Naujienos',
        'kalendorius' => 'Kalendorius',
        'baneriai' => 'Baneriai',
        'navigacija' => 'Navigacija',
        'greitosios_nuorodos' => 'Greitosios nuorodos',
        'renginiu_tipai' => 'Renginių tipai',
        'zymos' => 'Žymos',
        'failai' => 'Failai',
        'dokumentai' => 'Dokumentai',
        'studiju_rinkiniai' => 'Studijų rinkiniai',
        'nariai' => 'Nariai',
        'pareigybes' => 'Pareigybės',
        'pareigybiu_atnaujinimas' => 'Pareigybių atnaujinimas',
        'padaliniai' => 'Padaliniai',
        'studiju_programos' => 'Studijų programos',
        'registracija_nariai' => 'Narių registracija',
        'registracija_atstovai' => 'Studentų atstovų registracija',
        'formos' => 'Formos',
        'roles' => 'Rolės',
        'leidimai' => 'Leidimai',
        'tipai' => 'Tipai',
        'rysiai' => 'Ryšiai',
        'nustatymai' => 'Nustatymai',
        'sistemos_busena' => 'Sistemos būsena',
        'laisku_eile' => 'Laiškų eilė',
        'pagalbos_uzklausos' => 'Pagalbos užklausos',
        'sharepoint_failai' => 'Sharepoint failai',
    ],

    'actions' => [
        'new_meeting' => [
            'title' => 'Naujas susitikimas',
            'description' => 'Užfiksuok posėdį ir jo darbotvarkę',
        ],
        'no_meeting' => [
            'title' => 'Posėdžio nebuvo',
            'description' => 'Pažymėk, kad institucija šį periodą nesirinko',
        ],
        'complete_meeting' => [
            'title' => 'Užbaigti posėdį',
            'description' => 'Papildyk jau užregistruotą posėdį',
        ],
        'new_problem' => [
            'title' => 'Nauja problema',
            'description' => 'Praneškite apie studentams aktualią problemą',
        ],
        'new_reservation' => [
            'title' => 'Nauja rezervacija',
            'description' => 'Rezervuok patalpą ar įrangą',
        ],
        'new_news' => [
            'title' => 'Nauja naujiena',
            'description' => 'Parašyk naujieną svetainei',
        ],
        'duty_update' => [
            'title' => 'Pareigybių atnaujinimas',
            'description' => 'Atnaujink narius keliose pareigybėse iš karto',
        ],
        'duty_periods' => [
            'title' => 'Pareigybių laikotarpiai',
            'description' => 'Peržiūrėk ir tvarkyk kadencijas',
        ],
    ],
];
