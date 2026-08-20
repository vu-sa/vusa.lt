<?php

return [
    'title' => 'Kadencijos',
    'description' => 'Kadencijų pradžios ir pabaigos datos, pagal kurias tvarkomi pareigybių laikotarpiai.',

    'defaults' => [
        'title' => 'Numatytosios datos',
        'description' => 'Pagal šias datas užpildoma nauja kadencija. Datas visada galima pakeisti ranka.',
        'start_month_day' => 'Pradžios mėnuo ir diena',
        'end_month_day' => 'Pabaigos mėnuo ir diena',
        'preview' => 'Pavyzdys',
    ],

    'global' => [
        'title' => 'Bendros kadencijos',
        'description' => 'Galioja visoms institucijoms, neturinčioms savo kadencijų.',
        'empty' => 'Kadencijų dar nėra.',
    ],

    'overrides' => [
        'title' => 'Institucijų išimtys',
        'description' => 'Institucija, turinti bent vieną savo kadenciją, bendromis kadencijomis nesinaudoja. Išimtys tvarkomos pačios institucijos redagavimo lange.',
        'empty' => 'Išimčių nėra.',
        'count' => 'Kadencijų: :count',
        'open' => 'Atidaryti instituciją',
    ],

    'institution' => [
        'title' => 'Kadencijos',
        'description' => 'Datos, pagal kurias lygiuojami šios institucijos pareigybių laikotarpiai.',
        'override_active' => 'Bendrosios nebetaikomos',
        'inherited' => 'Paveldėtos bendros kadencijos',
        'inherited_hint' => 'Šios datos galioja tol, kol institucija neturi nė vienos savo kadencijos.',
        'own' => 'Šios institucijos kadencijos',
        'override_warning' => 'Pridėjus bent vieną savo kadenciją, institucija nustoja naudoti visas bendrąsias – net ir tų metų, kurių pati neaprašė. Prireikia retai.',
        'timeline' => 'Tvarkyti laikotarpius',
    ],

    'fields' => [
        'start_date' => 'Pradžia',
        'end_date' => 'Pabaiga',
        'institution' => 'Institucija',
    ],

    'actions' => [
        'add' => 'Pridėti kadenciją',
        'edit' => 'Redaguoti',
        'delete' => 'Ištrinti',
        'save' => 'Išsaugoti',
        'cancel' => 'Atšaukti',
    ],

    'delete' => [
        'title' => 'Ištrinti kadenciją?',
        'description' => 'Kadencija :label bus pašalinta. Pareigybių laikotarpiai nesikeis, bet nebeturės pagal ką lygiuotis.',
        'confirm' => 'Ištrinti',
        'cancel' => 'Atšaukti',
    ],
];
