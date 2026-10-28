<?php

return [
    // Settings index page
    'title' => 'Nustatymai',
    'description' => 'Valdyk sistemos nustatymus ir konfigūracijas.',

    // Settings categories
    'categories' => [
        'general' => 'Bendri nustatymai',
    ],

    // Settings pages
    'pages' => [
        'forms' => [
            'title' => 'Formų nustatymai',
            'description' => 'Konfigūruok formų nustatymus, pvz., narystės registraciją.',
        ],
        'meetings' => [
            'title' => 'Posėdžių rodymo nustatymai',
            'description' => 'Konfigūruok, kurių institucijų tipų posėdžiai rodomi viešai.',
        ],
        'documents' => [
            'title' => 'Dokumentų nustatymai',
            'description' => 'Konfigūruok, kurie dokumentų tipai rodomi pirmiausia kaip svarbiausi.',
        ],
        'atstovavimas' => [
            'title' => 'Atstovavimo nustatymai',
            'description' => 'Konfigūruok, kurie institucijų tipai laikomi studentų atstovų organais.',
        ],
        'cadences' => [
            'title' => 'Kadencijos',
            'description' => 'Nurodyk kadencijų pradžios ir pabaigos datas bei institucijų išimtis.',
        ],
        'site' => [
            'title' => 'Svetainės nustatymai',
            'description' => 'Nurodyk svetainės puslapius, į kuriuos nukreipia sistema, pvz., privatumo politiką.',
        ],
        'purposes' => [
            'title' => 'Sistemos paskirtys',
            'description' => 'Nurodyk, kuri atsakomybė ir kurie pareigybių tipai atlieka sistemos numatytas paskirtis.',
        ],
    ],

    // Form settings page
    'form_settings' => [
        'registration_form_title' => 'Narių registracijos forma',
        'registration_form_description' => 'Pasirinkti, kuri registracijos forma iš duombazės bus naudojama narių registracijai. Jeigu registracijos forma turi padalinio laukelį, automatiškai bus siunčiami laiškai užsiregistravusiems ir narių registracijos atsakomybę turintiems padalinio nariams.',
        'form_label' => 'Forma',
        'form_placeholder' => 'Pasirinkti formą',
        'recipients_title' => 'Kas gauna registracijas',
        'recipients_description' => 'Registracijas gauna ir mato atsakomybės „Narių registracija“ turėtojai savo padalinyje.',
        'recipients_link' => 'Atverti atsakomybę',
        'recipients_missing' => 'Narių registracijos paskirčiai dar nepriskirta jokia atsakomybė. Ją nurodai skiltyje „Sistemos paskirtys“.',
        'recipients_gaps' => 'Registracijų niekas negauna šiuose padaliniuose: :tenants.',
        'student_rep_title' => 'Studentų atstovų registracijos forma',
        'student_rep_description' => 'Pasirinkti, kuri forma bus naudojama studentų atstovų registracijai. Kai institucija neturi aktyvių atstovų, bus rodomas mygtukas užsiregistruoti.',
        'student_rep_form_label' => 'Studentų atstovų forma',
        'student_rep_types_label' => 'Institucijų tipai',
        'student_rep_types_description' => 'Pasirink, kuriuose institucijų tipuose bus rodomas registracijos mygtukas, kai nėra aktyvių atstovų.',
        'student_rep_types_placeholder' => 'Pasirink institucijų tipus',
        'no_types_found' => 'Tipų nerasta',
        'no_form_selected' => 'Nepasirinkta (išjungta)',
    ],

    // Meeting settings page
    'meeting_settings' => [
        'types_title' => 'Institucijų tipai su viešais posėdžiais',
        'types_description' => 'Pasirink, kurių institucijų tipų posėdžiai bus rodomi viešai kontaktų puslapiuose. Pavyzdžiui: studijų kolegija, KAP taryba, studijų programų komitetas.',
        'types_label' => 'Institucijų tipai',
        'types_placeholder' => 'Pasirinkti institucijų tipus',
        'no_types_found' => 'Institucijų tipų nerasta.',
    ],

    // Atstovavimas settings page
    'atstovavimas_settings' => [
        'baseline_title' => 'Atstovų rodiklių palyginimas',
        'baseline_label' => 'Palyginimo kadencija',
        'baseline_hint' => 'Rodikliai lyginami bendrų organizacijos kadencijų datomis. Pasirinkta kadencija skaičiuojama visa; dabartinė – iki šiandien. Institucijų kadencijų išimtys šio palyginimo nekeičia.',
        'baseline_automatic' => 'Automatiškai – ankstesnė baigta kadencija',
        'student_rep_type_title' => 'Studentų atstovų organų šakninis tipas',
        'student_rep_type_description' => 'Pasirink šakninį institucijos tipą, kuris (ir visi jo potipiai) laikomas studentų atstovų organu. Šio tipo institucijoms viešame kontaktų puslapyje bus rodoma studentų atstovų kortelė vietoje standartinės institucijos kortelės.',
        'student_rep_type_label' => 'Šakninis tipas',
        'student_rep_type_placeholder' => 'Nepasirinkta (išjungta)',
        'student_rep_type_note' => 'Nepasirinkus tipo, viešuose kontaktuose nerodomos studentų atstovų kortelės ir jų grupės. Atstovavimo rodikliai nuo šio pasirinkimo nepriklauso.',
        'counted_scopes_title' => 'Kurie organai skaičiuojami rodikliuose',
        'counted_scopes_description' => 'Padalinių statistika (atstovai, posėdžių registravimas, institucijų būklė) skaičiuojama tik pažymėtų organų institucijoms. Posėdžių grafike vis tiek matysi visas institucijas.',
        'counted_scopes_label' => 'Skaičiuojami organai',
        'counted_scopes' => [
            'vusa' => 'Parlamentas, Taryba, padalinių valdybos ir kiti VU SA organai.',
            'vu' => 'VU Senatas, Taryba, fakultetų tarybos, komitetai ir kiti VU organai, į kuriuos deleguojami studentų atstovai.',
            'national' => 'Lietuvos organai, pvz., LSS ar ministerijų darbo grupės.',
            'international' => 'Tarptautiniai organai, pvz., ESU.',
        ],
    ],

    // Document settings page
    'document_settings' => [
        'important_types_title' => 'Svarbiausi dokumentų tipai',
        'important_types_description' => 'Pasirink tipus, kuriuos rodyti pirmus filtre ir aukščiau tarp vienodai aktualių paieškos rezultatų.',
        'important_types_label' => 'Dokumentų tipai',
        'important_types_placeholder' => 'Pasirinkti dokumentų tipus',
        'no_types_found' => 'Dokumentų tipų nerasta.',
        'recommendations_title' => 'Rekomenduojami dokumentai',
        'recommendations_description' => 'Rekomendacija rodoma virš paieškos rezultatų, kai visi ieškomi žodžiai yra vienoje jos frazėje, pvz., „įstat“ atitinka „VU SA įstatai“. Frazes atskirk kableliais; galūnės atpažįstamos automatiškai.',
        'choose_document' => 'Pasirink dokumentą',
        'move_up' => 'Perkelti aukštyn',
        'move_down' => 'Perkelti žemyn',
        'remove' => 'Pašalinti',
        'phrases_label' => 'Rodyti ieškant',
        'add_recommendation' => 'Pridėti rekomendaciją',
        'when_empty' => 'Rodyti ir tuščioje paieškoje',
        'pause' => 'Laikinai išjungti',
        'phrases_placeholder' => 'frazės per kablelį, pvz., įstatai, VU SA įstatai',
        'change_document' => 'keisti dokumentą',
        'phrases_required' => 'Įrašyk bent vieną frazę arba pažymėk „Rodyti ir tuščioje paieškoje“.',
    ],

    // System purposes page
    'purposes' => [
        'intro' => 'Kai kurias atsakomybes ir pareigybių tipus sistema naudoja ne tik teisėms ar rodymui: pagal juos ji randa žmones ir grupuoja viešus kontaktus.',
        'responsibilities_title' => 'Atsakomybių paskirtys',
        'responsibilities_description' => 'Pagal šias atsakomybes sistema randa žmones. Kitos atsakomybės tik suteikia teises.',
        'responsibility_label' => 'Atsakomybė',
        'responsibility_none' => 'Nepriskirta',
        'duty_types_title' => 'Viešieji kontaktai',
        'duty_types_description' => 'Pagal šiuos pareigybių tipus grupuojami padalinių kontaktų puslapiai. Nuorodos nesikeičia.',
        'duty_type_label' => 'Pareigybių tipas',
        'fallback' => 'Nepasirinkta.',
        'duty_types' => [
            'coordinator' => [
                'label' => 'Koordinatoriai',
                'drives' => 'Puslapis /kontaktai/koordinatoriai ir koordinatorių vieta padalinio kontaktų sąraše.',
            ],
            'curator' => [
                'label' => 'Kuratoriai',
                'drives' => 'Puslapis /kontaktai/kuratoriai ir studijų programos laukelis, kai priskiri narį kuratoriaus pareigybei.',
            ],
            'mentor' => [
                'label' => 'Mentoriai',
                'drives' => 'Angliškas puslapis /contacts/mentors ir studijų programos laukelis, kai priskiri narį mentoriaus pareigybei.',
            ],
            'delete_blocked' => 'Šio tipo ištrinti negalima: jis naudojamas kaip :parts (Nustatymai → Sistemos paskirtys).',
        ],
        'one_each' => 'Viena atsakomybė gali atlikti tik vieną paskirtį.',
        'curator_not_mentor' => 'Kuratoriams ir mentoriams pasirink skirtingus tipus.',
    ],

    // Messages
    'messages' => [
        'updated' => 'Nustatymai atnaujinti sėkmingai.',
        'unauthorized' => 'Tu neturi teisių valdyti nustatymus.',
    ],

    // Breadcrumbs
    'breadcrumbs' => [
        'index' => 'Nustatymai',
        'forms' => 'Formų nustatymai',
        'meetings' => 'Posėdžių nustatymai',
        'documents' => 'Dokumentų nustatymai',
        'atstovavimas' => 'Atstovavimo nustatymai',
        'purposes' => 'Sistemos paskirtys',
    ],

    'site_settings' => [
        'privacy_page_title' => 'Privatumo politikos puslapis',
        'privacy_page_description' => 'Puslapiai, į kuriuos veda slapukų juostos nuoroda. Kiekvienai kalbai pasirink atskirą puslapį; jei viena kalba nepasirinkta, jos lankytojams bus rodoma kitos kalbos puslapio nuoroda.',
        'privacy_page_label' => 'Privatumo politikos puslapis',
        'privacy_page_placeholder' => 'Nepasirinkta',
        'privacy_page_search_placeholder' => 'Ieškoti puslapio pagal pavadinimą...',
        'privacy_page_empty' => 'Puslapių nerasta.',
    ],
];
