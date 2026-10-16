<?php

return [
    'lead' => 'Mailman konferencijos ir kas jas gauna. Automatiškai valdomose konferencijose gavėjai keičiasi kartu su pareigybėmis.',
    'search' => 'Ieškok pagal adresą ar pavadinimą…',
    'new' => 'Nauja konferencija',
    'import' => 'Atnaujinti iš Mailman',
    'not_configured' => 'Ryšys su Mailman nesukonfigūruotas, todėl rodomi paskutiniai išsaugoti duomenys.',
    'empty_title' => 'Konferencijų dar nėra',
    'empty_description' => 'Atnaujink sąrašą iš Mailman arba sukurk naują konferenciją.',

    'modes' => [
        'unmanaged' => 'Rankinis valdymas',
        'managed' => 'Automatinis valdymas',
    ],

    'sync_statuses' => [
        'synced' => 'Sutampa',
        'needs_review' => 'Laukia peržiūros',
        'failed' => 'Nepavyko',
    ],

    'fields' => [
        'required_note' => 'Žvaigždute (*) pažymėti laukai yra privalomi; kitus gali praleisti.',
        'address' => 'adresas',
        'local_part' => 'Adresas',
        'display_name' => 'Pavadinimas',
        'description' => 'Aprašymas',
        'tenant' => 'Padalinys',
        'members' => 'Gavėjai',
        'last_post' => 'Paskutinis laiškas',
        'posts_last_year' => 'Laiškų per metus',
        'mode' => 'Valdymas',
        'last_synced' => 'Sinchronizuota',
        'archive_policy' => 'Archyvas',
    ],

    'hints' => [
        'local_part' => 'Tik mažosios raidės, skaičiai, taškai ir brūkšneliai.',
        'tenant' => 'Kas gali tvarkyti šią konferenciją.',
    ],

    'archive_policies' => [
        'public' => 'Viešas',
        'private' => 'Privatus',
        'never' => 'Nearchyvuojama',
    ],

    'filters' => [
        'quiet' => 'Be laiškų per metus',
    ],

    'sections' => [
        'recipients' => 'Gavėjai',
        'sources' => 'Šaltiniai',
        'exclusions' => 'Išimtys',
        'archive' => 'Archyvas',
    ],

    'actions' => [
        'enable_managed' => 'Įjungti automatinį valdymą',
        'disable_managed' => 'Grąžinti rankinį valdymą',
        'sync' => 'Sinchronizuoti',
        'apply_review' => 'Peržiūrėjau, pritaikyti',
        'edit' => 'Redaguoti',
        'open_in_mailman' => 'Atidaryti archyvą Mailman',
        'copy_address' => 'Kopijuoti adresą',
        'address_copied' => 'Adresas nukopijuotas',
        'load_preview' => 'Įkelti skirtumus',
        'copy_failed' => 'Adreso nukopijuoti nepavyko.',
    ],

    'edit' => [
        'title' => 'Redaguoti konferenciją',
        'submit' => 'Išsaugoti',
    ],

    'create' => [
        'title' => 'Nauja konferencija',
        'description' => 'Konferencija bus sukurta Mailman su privačiu archyvu, o gavėjus nustatysi šaltiniais.',
        'submit' => 'Sukurti konferenciją',
    ],

    'confirm_managed' => [
        'title' => 'Įjungti automatinį valdymą?',
        'description' => 'Nuo šiol gavėjus nustato šaltiniai. Bus pridėta: :add, pašalinta: :remove. Pašalintieji laiško negaus.',
        'confirm' => 'Įjungti',
    ],

    'confirm_unmanaged' => [
        'title' => 'Grąžinti rankinį valdymą?',
        'description' => 'Gavėjai liks tokie, kokie yra dabar, bet nebesikeis kartu su pareigybėmis.',
        'confirm' => 'Grąžinti',
    ],

    'sources' => [
        'intro' => 'Gavėjai – visų šaltinių sąjunga, atėmus išimtis. Pareigybės skaičiuojamos tik galiojančios šiandien.',
        'add' => 'Pridėti šaltinį',
        'remove' => 'Pašalinti',
        'remove_confirm' => 'Pašalinti šaltinį „:name“?',
        'empty_title' => 'Šaltinių dar nėra',
        'empty_description' => 'Pridėk pareigybes, institucijas ar el. pašto adresus, kurie turi gauti šios konferencijos laiškus.',
        'missing' => 'Šaltinis ištrintas',
        'expired' => 'Pasibaigęs',
        'expires' => 'Galioja iki :date',
        'types' => [
            'duty' => 'Pareigybė',
            'duty_type' => 'Pareigybės tipas',
            'institution' => 'Institucija',
            'institution_type' => 'Institucijos tipas',
            'mailing_list' => 'Kita konferencija',
            'email' => 'El. paštas',
        ],
        'address_modes' => [
            'holder' => 'Asmeninis paštas',
            'duty_mailbox' => 'Pareigybės paštas',
        ],
        'address_mode_hints' => [
            'holder' => 'Pareigų el. paštas, jei nurodytas, kitaip – nario el. paštas.',
            'duty_mailbox' => 'Pareigybės dėžutė (pvz., atstovai@…), todėl keičiantis žmonėms adresas lieka tas pats.',
        ],
        'tenant_scopes' => [
            'all' => 'Visi',
            'main' => 'Centrinis',
            'padaliniai' => 'Padaliniai',
            'selected' => 'Pasirinkti',
        ],
        'include_subtypes' => 'Įtraukti potipius',
        'excluded_types' => 'Išskyrus tipus',
        'duty_types' => 'Tik pareigybių tipai',
        'tenants' => 'Padaliniai',
        'authorized_tenants' => 'Tavo padaliniai',
        'load_failed' => 'Šaltinių įkelti nepavyko. Bandyk dar kartą.',
        'retry' => 'Bandyti dar kartą',
    ],

    'sheet' => [
        'title' => 'Naujas šaltinis',
        'description' => 'Pasirink, iš kur imti gavėjus.',
        'submit' => 'Pridėti',
        'type' => 'Šaltinis',
        'target' => 'Kas',
        'pick' => 'Pasirink…',
        'address_mode' => 'Kuris adresas',
        'tenant_scope' => 'Kuriuose padaliniuose',
        'email' => 'El. paštas',
        'display_name' => 'Vardas',
        'note' => 'Pastaba',
        'note_hint' => 'Kodėl šis žmogus konferencijoje, kad kitas tvarkantis suprastų.',
        'expires_on' => 'Galioja iki',
        'filters' => 'Papildomi filtrai',
    ],

    'exclusions' => [
        'intro' => 'Šie adresai nebus konferencijoje, net jei atitinka šaltinį.',
        'add' => 'Pridėti išimtį',
        'remove' => 'Pašalinti',
        'empty' => 'Išimčių nėra.',
        'sheet_title' => 'Nauja išimtis',
        'submit' => 'Pridėti',
    ],

    'recipients' => [
        'empty' => 'Paskutiniame išsaugotame sąraše gavėjų nėra.',
        'unmanaged_note' => 'Rankiniu būdu valdomos konferencijos gavėjai rodomi taip, kaip yra Mailman.',
        'why' => 'Kodėl',
        'unknown_reason' => 'Pridėta Mailman',
    ],

    'diff' => [
        'title' => 'Skirtumai su Mailman',
        'in_sync' => 'Mailman gavėjai sutampa su šaltiniais.',
        'add' => 'Bus pridėti (:count)',
        'remove' => 'Bus pašalinti (:count)',
        'keep' => 'Palikti rankiniu būdu',
        'exclude' => 'Neįtraukti',
        'needs_review' => 'Sinchronizacija pašalintų daugiau nei pusę gavėjų, todėl laukia tavo peržiūros.',
        'preview_hint' => 'Prieš įjungdamas automatinį valdymą peržiūrėk, kas pasikeis. Reikalingus papildomus žmones palik rankiniu būdu.',
    ],

    'archive' => [
        'empty' => 'Archyve laiškų dar nėra.',
        'no_access' => 'Archyvą mato konferencijos gavėjai ir ją tvarkantys.',
        'attachments' => 'Priedai',
        'open' => 'Atidaryti Mailman',
        'show_more' => 'Rodyti visą laišką',
        'show_less' => 'Suskleisti',
        'importing' => 'Įkeliama archyvo istorija: išsaugota :count laiškų (paskutinis žinomas bendras skaičius: :total). Metų statistika bus rodoma baigus įkėlimą.',
    ],

    'spotlight' => [
        'title' => 'Konferencijos dabar čia',
        'body' => 'Matyk, kas gauna kiekvienos konferencijos laiškus ir kodėl, o gavėjus tegul atnaujina pareigybės.',
    ],

    'reasons' => [
        'manual' => 'Pridėta rankiniu būdu',
        'manual_with_note' => 'Pridėta rankiniu būdu: :note',
        'duty_mailbox' => 'Pareigybės paštas: :duty',
    ],

    'messages' => [
        'not_configured' => 'Ryšys su Mailman nesukonfigūruotas.',
        'imported' => 'Atnaujinta iš Mailman: naujų :created, atnaujintų :updated.',
        'synced' => 'Sinchronizuota: pridėta :added, pašalinta :removed.',
        'sync_failed' => 'Nepavyko sinchronizuoti su Mailman. Bandyk vėliau.',
        'preview_failed' => 'Nepavyko gauti gavėjų iš Mailman.',
        'needs_review' => 'Sinchronizacija pašalintų daug gavėjų, todėl laukia peržiūros.',
        'unmanaged' => 'Konferencija vėl valdoma rankiniu būdu.',
        'source_added' => 'Šaltinis pridėtas.',
        'source_removed' => 'Šaltinis pašalintas.',
        'exclusion_added' => 'Išimtis pridėta.',
        'exclusion_removed' => 'Išimtis pašalinta.',
        'source_forbidden' => 'Pasirink šaltinį iš padalinių, kuriuos gali tvarkyti.',
        'stale_preview' => 'Gavėjai pasikeitė arba peržiūra nebegalioja. Atnaujink skirtumus ir peržiūrėk juos dar kartą.',
        'create_failed' => 'Konferencijos sukurti nepavyko. Bandyk dar kartą; jei adresas jau yra, paprašyk centrinio koordinatoriaus atnaujinti iš Mailman.',
        'update_failed' => 'Konferencijos išsaugoti nepavyko. Bandyk vėliau.',
        'import_failed' => 'Konferencijų atnaujinti iš Mailman nepavyko. Bandyk vėliau.',
    ],
];
