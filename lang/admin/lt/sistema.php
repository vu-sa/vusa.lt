<?php

return [
    'overview' => [
        'title' => 'Apžvalga',
        'lead' => 'Ką platformos prižiūrėtojui verta pamatyti pirmiausia.',
        'new_requests' => 'Naujos pagalbos užklausos',
        'new_requests_empty' => 'naujų užklausų nėra',
        'status' => 'Sistemos būsena',
        'status_ok' => 'visos sistemos veikia',
        'status_problem' => ':check — :status',
        'status_link' => 'Išsami būsena',
        'checks' => [
            'redis' => 'Redis',
            'database' => 'Duomenų bazė',
            'cache' => 'Talpykla',
            'typesense' => 'Paieška',
            'scheduler' => 'Planuoklis',
            'digest' => 'Suvestinių laiškai',
            'mail' => 'Paštas',
        ],
        'statuses' => [
            'warning' => 'įspėjimas',
            'error' => 'klaida',
        ],
        'numbers' => [
            'open_requests' => 'Neišspręstos užklausos',
            'queued_mail' => 'Laiškų eilėje',
            'roles' => 'Rolės',
            'users' => 'Naudotojai',
            'future_duty_holders' => 'Nariai su būsimomis pareigomis',
        ],
    ],
    'maintenance' => [
        'title' => 'Priežiūra',
        'lead' => 'Veiksmai, kuriuos kitaip tektų paleisti serveryje. Kiekvienas įrašomas į veiklos žurnalą.',
        'run' => 'Paleisti',
        'queued_hint' => 'Vykdoma fone',
        'disruptive_hint' => 'Trumpam paveiks naudotojus',
        'confirm_title' => 'Paleisti „:action“?',
        'actions' => [
            'refresh-public-content' => [
                'label' => 'Atnaujinti viešo turinio talpyklą',
                'description' => 'Išvalo naujienų, puslapių, meniu, reklamjuosčių, kalendoriaus ir kitų viešų duomenų talpyklą. Naudok, kai pakeitimai nesimato svetainėje.',
            ],
            'clear-application-cache' => [
                'label' => 'Išvalyti visą talpyklą',
                'description' => 'Išvalo visą programos talpyklą. Prisijungimai išlieka, bet pirmi puslapiai kurį laiką kraunasi lėčiau.',
            ],
            'restart-queue-workers' => [
                'label' => 'Perkrauti eilės procesus',
                'description' => 'Liepia fono procesams baigti dabartinę užduotį ir pasileisti iš naujo. Naudok, kai užduotys užstrigo.',
            ],
            'send-test-mail' => [
                'label' => 'Išsiųsti bandomąjį laišką',
                'description' => 'Išsiunčia laišką tavo el. pašto adresu, kad patikrintum pašto nustatymus.',
            ],
            'sync-public-search' => [
                'label' => 'Sinchronizuoti viešą paiešką',
                'description' => 'Atnaujina naujienas, puslapius ir kontaktus viešoje paieškoje pagal jų dabartinę būseną.',
            ],
            'refresh-institution-activity' => [
                'label' => 'Atnaujinti institucijų aktyvumą',
                'description' => 'Perskaičiuoja institucijų aktyvumo būseną administravimo paieškoje.',
            ],
            'sync-sharepoint-documents' => [
                'label' => 'Sinchronizuoti SharePoint dokumentus',
                'description' => 'Atnaujina pasenusius dokumentus iš SharePoint (iki 50 vienu kartu).',
            ],
            'reindex-search' => [
                'label' => 'Perindeksuoti visą paiešką',
                'description' => 'Iš naujo sukuria visas Typesense kolekcijas. Apie minutę paieška gali grąžinti neišsamius rezultatus.',
            ],
        ],
    ],
];
