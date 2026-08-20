<?php

return [
    'hints' => [
        'title' => 'Trumpas, konkretus problemos pavadinimas, pvz. „Trūksta atsiskaitymo tvarkos aprašo“.',
        'description' => 'Aprašykite problemos esmę: kas, kur ir kada įvyko, ką tai paveikė.',
        'tenant' => 'Padalinys, kuriame problema užfiksuota. Pagal jį filtruojamas institucijų sąrašas.',
        'status' => 'Atvira — dar nespręsta; Vykdoma — šiuo metu sprendžiama; Išspręsta — užbaigta.',
        'occurred_at' => 'Data, kada problema įvyko arba buvo pastebėta.',
        'resolved_at' => 'Užpildykite tik tada, kai problema jau išspręsta.',
        'responsible_user' => 'Asmuo, atsakingas už problemos sprendimą. Ieškokite pagal vardą.',
        'categories' => 'Pasirinkite vieną ar kelias kategorijas — prie kiekvienos rodomas aprašymas, padėsiantis apsispręsti.',
        'institutions' => 'Institucijos (pvz. fakulteto taryba, SPK), su kuriomis problema susijusi.',
        'steps_taken' => 'Kas jau buvo daryta: pokalbiai, raštai, susitikimai ir jų rezultatai.',
        'solution' => 'Galutinis problemos sprendimas. Galima palikti tuščią ir užpildyti vėliau.',
    ],
    'validation' => [
        'title_required' => 'Problemos pavadinimas turi būti nurodytas bent viena kalba.',
        'description_required' => 'Problemos aprašymas turi būti nurodytas bent viena kalba.',
        'tenant_required' => 'Padalinys yra privalomas.',
        'tenant_exists' => 'Pasirinktas padalinys neegzistuoja.',
        'occurred_at_required' => 'Įvykimo data yra privaloma.',
        'resolved_at_after' => 'Išsprendimo data negali būti ankstesnė nei įvykimo data.',
        'status_in' => 'Pasirinkta būsena yra neteisinga.',
        'categories_exist' => 'Viena ar kelios pasirinktos kategorijos neegzistuoja.',
        'institutions_exist' => 'Viena ar kelios pasirinktos institucijos neegzistuoja.',
    ],
];
