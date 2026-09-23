<?php

/**
 * Labels for the navigation catalog (App\Services\AdminNavigation\AdminNavigationCatalog).
 * Keys are ASCII-safe (never the accented workspace/section name) so they stay stable across
 * relabelling — the label itself lives only here.
 */
return [
    'merge' => [
        'redirect' => 'Įrašų sujungimą dabar rasite sąrašo veiksmuose.',
    ],
    'workspaces' => [
        'pradzia' => [
            'title' => 'Mano',
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

    'chrome' => [
        'product' => 'Mano VU SA',
        'workspaces' => 'Skyriai',
        'sections_nav' => 'Skyriaus puslapiai',
        'main_nav' => 'Pagrindinė naršymo juosta',
        'all_sections' => 'Visi skyriai',
        'all_sections_lead' => 'Viskas, ką gali atidaryti su šia paskyra.',
        'find_section' => 'Ieškoti skyriaus',
        'no_sections' => 'Skyrių nerasta',
        'tools' => 'Įrankiai',
        'create' => 'Sukurti',
        'search' => 'Ieškoti',
        'search_field' => 'Ieškoti ar pereiti…',
        'menu' => 'Meniu',
        'close_menu' => 'Uždaryti meniu',
        'account' => 'Paskyra',
        'breadcrumbs' => 'Puslapio kelias',
        'back_to' => 'Atgal į :page',
    ],

    'account' => [
        'appearance' => 'Išvaizda', 'light' => 'Šviesi tema', 'dark' => 'Tamsi tema', 'language' => 'Kalba: :language',
        'help' => 'Pagalba', 'docs' => 'Dokumentacija', 'tour' => 'Parodyk, kaip veikia', 'report_problem' => 'Pranešti apie problemą',
        'my_requests' => 'Mano užklausos', 'roles' => 'Mano rolės ir pareigybės', 'notifications' => 'Pranešimų nustatymai', 'whats_new' => 'Kas naujo', 'start_fm' => 'Klausyti START FM', 'about' => 'Apie',
        'listen' => 'Klausyti', 'pause' => 'Pristabdyti', 'close' => 'Uždaryti',
    ],

    'badges' => [
        'tasks_pending' => 'Laukiančios užduotys: :count',
        'tasks_overdue' => 'Laukiančios užduotys: :count, iš jų vėluoja: :overdue',
    ],

    'palette' => [
        'go_to' => 'Pereiti į',
        'create' => 'Sukurti',
        'pinned' => 'Prisegta',
        'recent' => 'Neseniai',
        'pin' => 'Prisegti',
        'unpin' => 'Atsegti',
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
        'rep_metrics' => 'Atstovų rodikliai',
        'pagalbos_uzklausos' => 'Pagalbos užklausos',
        'sharepoint_failai' => 'Sharepoint failai',
    ],

    'actions' => [
        'merge' => [
            'title' => 'Sujungti įrašus',
        ],
        'new_meeting' => [
            'title' => 'Fiksuoti posėdį',
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
