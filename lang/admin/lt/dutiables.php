<?php

return [
    'timeline' => [
        'title' => 'Laikotarpių tvarkyklė',
        'description' => 'Peržiūrėk ir tvarkyk pareigybių laikotarpius vienoje vietoje.',
        'open' => 'Tvarkyti laikotarpius',
        'show_ended' => 'Rodyti pasibaigusius',
        'ended_hidden' => 'Pasibaigę laikotarpiai paslėpti',
        'collapse_all' => 'Suskleisti visus',
        'expand_all' => 'Išskleisti visus',
        'collapse_group' => 'Suskleisti pareigybę',
        'expand_group' => 'Išskleisti pareigybę',
        'truncated' => 'Rodomi tik pirmi :max įrašai. Pasirink konkrečias pareigybes.',
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
            'open_panel' => 'Pažymėta ir pasiūlymai',
            'selection_hint' => 'Pažymėk juostą grafike arba varnelę šalia vardo.',
        ],

        'legend' => [
            'title' => 'Žymėjimai',
            'active' => 'Dabartinis',
            'former' => 'Pasibaigęs',
            'derived' => 'Ex officio (dabartinis)',
            'staged' => 'Neišsaugotas pakeitimas',
            'cross_tenant' => 'Atstovauja kitam padaliniui',
        ],

        'duration' => [
            'label' => 'Trukmė',
            'year' => ':value m.',
            'month' => ':value mėn.',
            'day' => ':value d.',
        ],

        'sort' => [
            'label' => 'Rikiuoti',
            'default' => 'Numatytoji tvarka',
            'study_program' => 'Pagal studijų programą',
        ],

        'help' => [
            'title' => 'Kaip keisti',
            'drag_body' => 'Tempk juostą – ji slenka mėnesiais, mėnesio diena išlieka.',
            'drag_edges' => 'Tempk juostos kraštą, kad pakeistum pradžią ar pabaigą; kraštas pritraukiamas prie kadencijos ribos.',
            'precise' => 'Alt – be pritraukimo, tiksliai dienai. Ctrl (⌘) – kartu visiems pažymėtiems.',
            'cancel' => 'Esc – atšaukti tempimą.',
            'fullscreen' => 'Visas ekranas – daugiau vietos grafikui; Esc grąžina atgal.',
        ],

        'filters' => [
            'cadence' => 'Kadencija',
            'view' => 'Rodinys',
            'tenant' => 'Padalinys',
            'clear' => 'Išvalyti filtrą',
            'no_cadence' => 'Be kadencijos',
            'no_tenant' => 'Be padalinio',
            'empty_title' => 'Pagal filtrą nieko nerasta',
            'empty_description' => 'Išvalyk kadencijos arba padalinio filtrą, kad matytum daugiau įrašų.',
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
            'empty' => 'Pasirink juostą, kad matytum tikslias datas.',
            'start_date' => 'Pradžia',
            'end_date' => 'Pabaiga',
            'open_ended_toggle' => 'Palikti neterminuotą',
            'ex_officio' => 'Ex officio',
            'ex_officio_managed' => 'Šios datos sekamos iš pareigybės „:duty“ ir keičiamos tik ten.',
            'select_source' => 'Pažymėti šaltinio įrašą',
            'not_editable' => 'Šio įrašo keisti negali.',
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
            'self_affecting' => 'Tarp keičiamų įrašų yra tavo paties pareigybė. Išsaugojus gali tekti patvirtinti prieigos pakeitimą.',
            'diagnostics_delta' => 'Problemos: :before → :after',
            'confirm' => 'Išsaugoti',
            'cancel' => 'Grįžti',
        ],

        'blocked' => [
            'derived' => 'Ex officio įrašas – datos sekamos iš šaltinio.',
            'inverted' => 'Pabaiga būtų anksčiau už pradžią.',
        ],

        'diagnostics' => [
            'advisory' => 'Ne visi siūlomi taisymai yra privalomi ar teisingi.',
            'empty' => 'Neatitikimų nerasta.',
            'apply_selected' => 'Taikyti pažymėtus (:count)',
            'codes' => [
                'inverted' => 'Pabaiga anksčiau už pradžią',
                'overlap' => 'Persidengiantys laikotarpiai',
                'boundary_shared' => 'Vienas laikotarpis baigiasi kito pradžios dieną',
                'open_ended_stale' => 'Neterminuota nuo ankstesnės kadencijos',
                'ex_officio_drift' => 'Ex officio datos nesutampa su šaltiniu',
                'off_cadence' => 'Data nesutampa su kadencijos riba',
                'spans_cadences' => 'Perrinkta kelioms kadencijoms',
                'understaffed' => 'Užimta mažiau vietų, nei numatyta',
                'orphan_derived_suspect' => 'Įtartinas ex officio įrašas be šaltinio',
            ],
            'detail' => [
                'end_move' => 'pabaiga :from → :to',
                'clear_end' => 'pabaiga bus išvalyta',
                'close_at' => 'jei pareigų nebeeina – užbaigti :date',
                'drift_start' => 'pradžia nutolusi :days d.',
                'drift_end' => 'pabaiga nutolusi :days d.',
                'spans' => 'kadencijų: :count',
                'understaffed' => 'užimta :active iš :places vietų',
                'ex_officio_drift' => 'Tvarkoma perkeliant šaltinio įrašą.',
            ],
            'orphan_note' => 'Šie įrašai suteikia realias teises, o nuoroda į šaltinį jau ištrinta, todėl automatiškai jų liesti negalima. Paleisk „duties:audit-ex-officio“.',
        ],

        'fullscreen' => [
            'enter' => 'Visas ekranas',
            'exit' => 'Išeiti iš viso ekrano',
            'region' => 'Pareigybių laikotarpių grafikas',
        ],

        'page' => [
            'title' => 'Laikotarpių tvarkyklė',
            'eyebrow' => 'ViSAK · Laikotarpių tvarkyklė',
            'open_institution' => 'Atidaryti instituciją',
            'description' => 'Visų institucijos pareigybių laikotarpiai vienoje laiko juostoje: kas, kada ir kiek laiko ėjo pareigas.',
            'pick_institution' => 'Pasirinkti instituciją',
            'change_institution' => 'Keisti instituciją',
            'your_institutions' => 'Tavo institucijos',
            'search_all' => 'Ieškoti tarp visų institucijų…',
            'no_scope' => 'Pasirink instituciją, kad matytum laikotarpius.',
        ],

    ],

    'assign' => [
        'already_assigned' => 'Šis narys tuo laikotarpiu jau eina šias pareigas.',
        'quota_exceeded' => 'Padalinio kvota (:quota) viršyta.',
        'last_day_notice' => 'Šiandien yra paskutinė pareigų diena. Narys lieka aktyvus iki dienos pabaigos, o laikotarpis išlieka istorijoje.',
        'still_active_today' => ':name šiandien dar eina pareigas. Laikotarpis baigsis po šiandien ir išliks istorijoje.',
    ],
];
