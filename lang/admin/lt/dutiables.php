<?php

return [
    'timeline' => [
        'title' => 'Pareigybių laikotarpiai',
        'description' => 'Peržiūrėkite ir tvarkykite pareigybių laikotarpius vienoje vietoje.',
        'open' => 'Tvarkyti laikotarpius',
        'row_count' => 'Įrašų: :count',
        'show_ended' => 'Rodyti pasibaigusius',
        'collapse_all' => 'Suskleisti visus',
        'expand_all' => 'Išskleisti visus',
        'truncated' => 'Rodomi tik pirmi :max įrašai. Pasirinkite konkrečias pareigybes.',
        'blocked_summary' => '{1} :count įrašas praleistas|[2,9] :count įrašai praleisti|[10,*] :count įrašų praleista',
        'select_group' => 'Pažymėti visus grupėje',

        'empty' => [
            'title' => 'Laikotarpių nėra',
            'description' => 'Šiai peržiūrai nerasta nė vieno pareigybės laikotarpio.',
        ],

        'dock' => [
            'selection' => 'Pažymėta',
            'suggestions' => 'Siūlomi taisymai',
            'multi_hint' => 'Keisti galima :editable iš :total pažymėtų įrašų.',
            'save' => 'Pakeitimai',
        ],

        'zoom' => [
            'label' => 'Mastelis',
            'in' => 'Priartinti',
            'out' => 'Atitolinti',
        ],

        'legend' => [
            'title' => 'Žymėjimai',
            'active' => 'Dabartinis',
            'former' => 'Pasibaigęs',
            'derived' => 'Ex officio (dabartinis)',
            'staged' => 'Neišsaugotas pakeitimas',
            'cross_tenant' => 'Atstovauja kitam padaliniui',
        ],

        'filters' => [
            'cadence' => 'Kadencija',
            'tenant' => 'Padalinys',
            'clear' => 'Išvalyti filtrą',
            'no_cadence' => 'Be kadencijos',
            'no_tenant' => 'Be padalinio',
            'empty_title' => 'Pagal filtrą nieko nerasta',
            'empty_description' => 'Išvalykite kadencijos arba padalinio filtrą, kad matytumėte daugiau įrašų.',
        ],

        'extras' => [
            'title' => 'Papildoma informacija',
            'email' => 'El. paštas',
            'study_program' => 'Studijų programa',
            'study_program_note' => 'Grupė ar pastaba',
            'description' => 'Aprašymas',
            'photo' => 'Nuotrauka',
            'photo_set' => 'Įkelta atskira nuotrauka',
            'original_duty_name' => 'Pareigybės pavadinimas',
            'original_duty_name_set' => 'Rodomas originalus pareigybės pavadinimas',
        ],

        'inspector' => [
            'empty' => 'Pasirinkite juostą, kad matytumėte tikslias datas.',
            'start_date' => 'Pradžia',
            'end_date' => 'Pabaiga',
            'open_ended_toggle' => 'Palikti neterminuotą',
            'ex_officio' => 'Ex officio',
            'ex_officio_managed' => 'Šios datos sekamos iš pareigybės „:duty“ ir keičiamos tik ten.',
            'select_source' => 'Pažymėti šaltinio įrašą',
            'aligned' => 'Sutampa su kadencijos pradžia.',
            'off_by' => 'Nuo kadencijos pradžios skiriasi :days d.',
            'not_editable' => 'Šio įrašo keisti negalite.',
        ],

        'actions' => [
            'apply_dates' => 'Taikyti datas (:count)',
            'merge' => 'Sujungti',
            'merge_title' => 'Sujungti laikotarpius?',
            'merge_description' => ':count :holder laikotarpiai pareigose „:duty“ taps vienu: :start → :end. Kiti įrašai bus ištrinti.',
            'merge_extras_warning' => 'Keli įrašai turi papildomos informacijos. Sujungus liks tik pirmojo – kitų el. paštas, studijų programa ar aprašymas bus prarasti.',
            'merge_confirm' => 'Sujungti',
            'merge_hint' => 'Sujungti galima tik to paties žmogaus tos pačios pareigybės laikotarpius.',
            'merge_invalid' => 'Sujungti galima tik to paties žmogaus tos pačios pareigybės laikotarpius.',
            'merge_done' => 'Sujungta laikotarpių: :count.',
            'align' => 'Lygiuoti',
            'close' => 'Užbaigti',
            'close_end_date' => 'Pabaigos data',
            'close_yesterday' => 'Vakar dienos data (:date)',
            'close_hint' => 'Taip pareigybės užbaigiamos ir kitose sistemos vietose.',
            'close_run' => 'Peržiūrėti pakeitimus',
            'remove' => 'Pašalinti',
            'remove_title' => 'Pašalinti šį pareigybės laikotarpį?',
            'remove_description' => ':holder nebeeis pareigų „:duty“ šiuo laikotarpiu, kartu dings ir su jomis suteiktos teisės. Atkurti nebus galima.',
            'remove_confirm' => 'Pašalinti',
            'remove_cancel' => 'Atšaukti',
        ],

        'drag' => [
            'hint' => 'Tempkite juostą – mėnuo į šoną, diena išlieka. Kraštus tempkite pabaigai keisti, Alt – tiksliai dienai, Esc – atšaukti.',
        ],

        'staging' => [
            'dirty_count' => '{1} :count nesaugotas pakeitimas|[2,9] :count nesaugoti pakeitimai|[10,*] :count nesaugotų pakeitimų',
            'clean' => 'Viskas išsaugota.',
            'preview' => 'Peržiūrėti',
            'discard' => 'Atšaukti',
            'save' => 'Išsaugoti',
            'saving' => 'Saugoma…',
            'sync_pending' => 'Sinchronizuojami ex officio įrašai',
        ],

        'diff' => [
            'title' => 'Pakeitimų peržiūra',
            'description' => 'Taip atrodys įrašai po išsaugojimo.',
            'changed' => 'Keisis: :count',
            'blocked' => 'Praleista: :count',
            'unchanged' => 'Nesikeis: :count',
            'derived' => 'Ex officio įrašų seks: :count',
            'no_changes' => 'Pakeitimų nėra.',
            'self_affecting' => 'Tarp keičiamų įrašų yra jūsų pačių pareigybė. Išsaugojus gali tekti patvirtinti prieigos pakeitimą.',
            'diagnostics_delta' => 'Problemos: :before → :after',
            'confirm' => 'Išsaugoti',
            'cancel' => 'Grįžti',
        ],

        'blocked' => [
            'derived' => 'Ex officio įrašas – datos sekamos iš šaltinio.',
            'inverted' => 'Pabaiga būtų anksčiau už pradžią.',
        ],

        'diagnostics' => [
            'empty' => 'Neatitikimų nerasta.',
            'apply_selected' => 'Taikyti pažymėtus (:count)',
            'codes' => [
                'inverted' => 'Pabaiga anksčiau už pradžią',
                'overlap' => 'Persidengiantys laikotarpiai',
                'boundary_shared' => 'Vienas laikotarpis baigiasi kito pradžios dieną',
                'open_ended_stale' => 'Neterminuota, nors kadencija jau baigėsi',
                'ex_officio_drift' => 'Ex officio datos nesutampa su šaltiniu',
                'off_cadence' => 'Data nesutampa su kadencijos riba',
                'spans_cadences' => 'Apima daugiau nei vieną kadenciją',
                'understaffed' => 'Užimta mažiau vietų, nei numatyta',
                'orphan_derived_suspect' => 'Įtartinas ex officio įrašas be šaltinio',
            ],
            'detail' => [
                'end_move' => 'pabaiga :from → :to',
                'clear_end' => 'pabaiga bus išvalyta',
                'close_at' => 'užbaigti :date',
                'drift_start' => 'pradžia nutolusi :days d.',
                'drift_end' => 'pabaiga nutolusi :days d.',
                'spans' => 'kadencijų: :count · taps :start → :end',
                'understaffed' => 'užimta :active iš :places vietų',
                'ex_officio_drift' => 'Tvarkoma perkeliant šaltinio įrašą.',
            ],
            'orphan_note' => 'Šie įrašai suteikia realias teises, o nuoroda į šaltinį jau ištrinta, todėl automatiškai jų liesti negalima. Paleiskite „duties:audit-ex-officio“.',
        ],

        'page' => [
            'title' => 'Pareigybių laikotarpiai',
            'description' => 'Institucijos mastu peržiūrėkite ir sutvarkykite visų pareigybių laikotarpius.',
            'pick_institution' => 'Pasirinkti instituciją',
            'change_institution' => 'Keisti instituciją',
            'no_scope' => 'Pasirinkite instituciją, kad matytumėte laikotarpius.',
        ],

        'spotlight' => [
            'title' => 'Laikotarpius galima tvarkyti vienoje vietoje',
            'description' => 'Vietoj to, kad kiekvieno nario laikotarpį redaguotumėte atskirai, atidarykite laiko juostą ir sutvarkykite visus iš karto.',
        ],
    ],
];
