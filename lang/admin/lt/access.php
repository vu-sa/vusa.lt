<?php

return [
    'title' => 'Atsakomybės ir pareigybės',
    'lead' => 'Čia matai, kokias pareigybes ir atsakomybes turi ir kur jos tau leidžia patekti. Jei ko trūksta, kreipkis į savo koordinatorių.',
    'link' => 'Mano atsakomybės ir pareigybės',
    'current' => [
        'title' => 'Dabartinės pareigybės',
        'empty' => 'šiuo metu pareigybių neturi',
    ],
    'direct' => [
        'title' => 'Tiesiogiai priskirtos atsakomybės',
        'empty' => 'tiesiogiai priskirtų atsakomybių nėra',
    ],
    'capabilities' => [
        'title' => 'Ką gali daryti',
        'empty' => 'kol kas negali atverti jokio skyriaus',
        'super_admin' => 'Esi sistemos administratorius: gali atverti visus skyrius.',
    ],
    'upcoming' => [
        'title' => 'Būsimos pareigybės',
    ],
    'ended' => [
        'title' => 'Kadencijų istorija',
    ],
    'band' => [
        'started' => 'Tavo prieiga pasikeitė: nuo :date turi pareigybę „:duty“.',
        'ended' => 'Tavo prieiga pasikeitė: nuo :date nebeturi pareigybės „:duty“.',
        'many' => 'Tavo prieiga pasikeitė: :count pareigybių pakeitimai.',
        'view' => 'Peržiūrėti',
        'dismiss' => 'Gerai',
    ],
    'history' => [
        'title' => 'Prieigos pasikeitimai',
        'empty' => 'per pastaruosius metus tavo pareigybės nesikeitė',
        'started' => 'Pradėta pareigybė „:duty“',
        'ended' => 'Pareigybė „:duty“ baigėsi',
    ],
    'term' => [
        'until_now' => 'dabar',
        'ex_officio' => 'Ex officio',
        'represents' => 'Atstovauji: :tenant',
        'roles' => 'Atsakomybės: :roles',
    ],
    'duty_period' => 'tavo pareigų laikotarpį',
    'own_institutions' => 'Savo institucijų, visų kadencijų',
    'own_institution_documents' => 'Savo institucijų',
    'permission_scopes' => [
        'meetings' => [
            'read' => [
                'padalinys' => 'Neviešiems klausimams matyti reikia ir klausimų peržiūros teisės.',
                '*' => 'Neviešiems klausimams matyti reikia ir klausimų peržiūros teisės.',
            ],
        ],
        'agendaItems' => [
            'create' => [
                'padalinys' => 'Klausimus galima pridėti į matomus posėdžius be teisės redaguoti posėdį.',
                '*' => 'Klausimus galima pridėti į matomus posėdžius be teisės redaguoti posėdį.',
            ],
        ],
    ],
    'baseline' => [
        'title' => 'Visi nariai – ir be atsakomybės',
        'locked' => 'Visi nariai',
        'locked_own' => 'Savo – visi nariai',
        'institutions' => 'Mato institucijų sąrašą ir aktyvių institucijų viešą informaciją, o savo dabartines, su jomis susietas ir sekretoriumi prižiūrimas institucijas – pilnai.',
        'meetings' => 'Matai viešus posėdžius, nepasibaigusių pareigybių institucijų posėdžius, ryšiais pasiekiamų institucijų ir institucijų, kurioms esi paskirtas sekretoriumi, posėdžius. Savo institucijų posėdžius, kurių data patenka į :period, gali matyti ir redaguoti ir pareigoms pasibaigus.',
        'agendaItems' => 'Matai viešų posėdžių viešus klausimus. Savo institucijų posėdžių, kurių data patenka į :period, klausimus ir balsavimus matai, kuri, redaguoji ir trini ir pareigoms pasibaigus. Nepasibaigusių pareigybių, ryšiais pasiekiamų ir sekretoriumi prižiūrimų institucijų klausimus matai. Posėdžio redagavimo teisė leidžia tvarkyti jo klausimus.',
        'problems' => 'Mato visų padalinių problemas.',
        'resources' => 'Mato visus išteklius.',
        'documents' => 'ViSAK paskelbtus dokumentus naršo visi nariai. Šios teisės skirtos dokumentų tvarkymui: laukiančių ir paslėptų failų matymui, paskelbimui ir slėpimui.',
        'duties' => 'Mato savo dabartines ir buvusias pareigybes bei kadencijas.',
        'dutiables' => 'Savo pareigybes ir jų kadencijas mato visi nariai; kitų kadencijas valdo pareigybes tvarkantys.',
        'tasks' => 'Mato ir atlieka jiems priskirtas užduotis.',
        'comments' => 'Komentuoja visur, kur mato įrašą; savo komentarą gali redaguoti 15 min. po parašymo, o ištrinti – bet kada. Kitų komentarus trina tas, kas gali redaguoti įrašą.',
        'reservations' => 'Kiekvienas gali kurti rezervacijas, mato ir keičia tas, kuriose dalyvauja, o savo rezervaciją gali ištrinti, kol ji dar nepatvirtinta.',
        'users' => 'Kiekvienas mato ir redaguoja save (Profilis), bet negali savęs ištrinti.',
    ],
];
