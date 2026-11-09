<?php

return [
    'entity' => ['singular' => 'Mokymosi žingsnis', 'plural' => 'Mokymosi žingsniai'],
    'tabs' => [
        'path' => 'Mano kelias',
        'library' => 'Žinynas',
        'glossary' => 'Sąvokos',
        'handover' => 'Perdavimas',
        'reps' => 'Mano atstovai',
    ],
    'pages' => [
        'path' => 'Mano kelias',
        'library' => 'Žinynas',
        'glossary' => 'Sąvokos',
        'handover' => 'Perdavimas',
        'reps' => 'Mano atstovai',
        'step' => 'Mokymosi žingsnis',
        'create' => 'Sukurti mokymosi žingsnį',
        'edit' => 'Redaguoti mokymosi žingsnį',
        'rep' => 'Atstovo kelias',
    ],
    'kinds' => [
        'knowledge' => 'Žinios',
        'action' => 'Veiksmas',
        'meeting_prep' => 'Pasiruošimas posėdžiui',
    ],
    'layers' => [
        'platform' => 'Platforma',
        'foundation' => 'Pagrindas',
        'institution_type' => 'Institucijos tipas',
        'duty_type' => 'Pareigos',
        'tenant' => 'Padalinys',
    ],
    'stages' => [
        'before_start' => 'Prieš pradžią',
        'first_months' => 'Pirmieji mėnesiai',
        'whole_term' => 'Visa kadencija',
        'term_end' => 'Kadencijos pabaiga',
    ],
    'importance' => [
        'essential' => 'Esminis',
        'recommended' => 'Rekomenduojamas',
        'optional' => 'Papildomas',
    ],
    'states' => [
        'pending' => 'Laukia',
        'done' => 'Atlikta',
        'skipped' => 'Praleista',
        'updated' => 'Atnaujinta',
    ],
    'transparency' => 'Tavo pažangą mato koordinatorius: :names',
    'empty' => [
        'title' => 'Čia bus tavo mokymosi erdvė',
        'description' => 'Ruošiame žingsnius, sąvokas ir patirties perdavimą.',
    ],
    'validation' => [
        'stage' => 'Pasirink šiam žingsniui tinkamą etapą.',
        'source' => 'Pasirink dokumentą arba įrašyk nuorodą.',
        'progress' => 'Šio žingsnio taip pažymėti negali.',
    ],
    'system_steps' => [
        'check_access' => [
            'title' => 'Patikrink savo pareigas ir prieigą',
            'description' => 'Patikrink institucijas, pareigas, jų datas ir prieinamas skiltis. Jei ko nors trūksta ar matai klaidą, pranešk administratoriui.',
            'action' => 'Peržiūrėti savo atsakomybes ir pareigas',
        ],
        'review_contacts' => [
            'title' => 'Peržiūrėk savo kontaktinius duomenis',
            'description' => 'Paskyroje gali pataisyti kontaktus, nuotrauką ir kreipinį. Prisijungimo el. paštas čia nekeičiamas, o vardą gali pataisyti tik vieną kartą. Nuotrauka ir telefono numeris šiam žingsniui neprivalomi.',
            'action' => 'Atverti paskyrą',
        ],
        'notification_settings' => [
            'title' => 'Pasirink, kaip gauti pranešimus',
            'description' => 'Patikrink, kuriais el. pašto adresais gausi laiškus, pasirink suvestinės dažnumą ir svarbias pranešimų rūšis. Numatytuosius nustatymus gali palikti. Jei nori, nustatymuose išsiųsk bandomąjį laišką.',
            'action' => 'Peržiūrėti pranešimų nustatymus',
        ],
        'profile_photo' => [
            'title' => 'Pridėk savo nuotrauką',
            'description' => 'Nuotrauka padės kolegoms tave atpažinti. Ją gali pakeisti savo paskyroje.',
            'action' => 'Atverti paskyrą',
        ],
        'install_app' => [
            'title' => 'Įsidiek Mano VU SA',
            'description' => 'Pridėk Mano VU SA prie telefono pradžios ekrano arba kompiuterio programų, kad kitą kartą atvertum vienu paspaudimu. Įdiegti neprivaloma – gali naudotis naršykle.',
            'action' => 'Įdiegti',
        ],
        'browser_push' => [
            'title' => 'Įjunk naršyklės pranešimus',
            'description' => 'Gauk priminimus ir kitus pasirinktus pranešimus tiesiai į šį įrenginį. Naršyklė leidimo paklaus tik tau paspaudus įjungimo mygtuką. Atsisakius laiškų ir pranešimų platformoje nustatymai nesikeičia.',
            'action' => 'Įjungti pranešimus',
        ],
        'orientation' => [
            'title' => 'Sužinok, nuo ko pradėti',
            'description' => 'Pradžioje pirmiausia matysi darbus, kuriems reikia tavo dėmesio. Darbo sričių meniu rodo tik tau prieinamas skiltis. Pagalba, gidas ir interaktyvus turas pasiekiami paskyros meniu.',
            'action' => 'Peržiūrėti savo užduotis',
        ],
        'meet_coordinator' => [
            'title' => 'Susipažink su koordinatoriumi',
            'description' => 'Susitark, kaip bendrausite ir kur kreipsiesi prireikus pagalbos.',
            'action' => 'Susisiekti',
        ],
        'follow_institution' => [
            'title' => 'Sek savo instituciją',
            'description' => 'Sek institucijos veiklą, kad gautum tau svarbius pranešimus.',
            'action' => 'Sekti instituciją',
        ],
        'meet_institution_contacts' => [
            'title' => 'Susipažink su institucijos kontaktais',
            'description' => 'Sužinok, kas rengia posėdžius ir į ką gali kreiptis dėl darbotvarkės.',
            'action' => 'Peržiūrėti kontaktus',
        ],
        'record_first_meeting' => [
            'title' => 'Užfiksuok pirmą posėdį',
            'description' => 'Po posėdžio užfiksuok svarstytus klausimus ir priimtus sprendimus.',
            'action' => 'Fiksuoti posėdį',
        ],
        'read_handover' => [
            'title' => 'Perskaityk perduotas pastabas',
            'description' => 'Peržiūrėk ankstesnio atstovo patirtį, nebaigtus darbus ir patarimus.',
            'action' => 'Skaityti pastabas',
        ],
        'write_handover' => [
            'title' => 'Perduok patirtį kitam atstovui',
            'description' => 'Aprašyk nebaigtus darbus, naudingus kontaktus ir tai, ką verta žinoti tavo įpėdiniui.',
            'action' => 'Rašyti pastabas',
        ],
    ],
];
