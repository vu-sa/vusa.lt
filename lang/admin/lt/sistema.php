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
        ],
    ],
];
