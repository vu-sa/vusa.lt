<?php

return [
    'form' => [
        'name_hint' => 'Esamų vardų ir pavardžių keisti negalima — kitam asmeniui sukurk naują narį.',
        'contacts_hint' => 'Turinčio pareigybę nario nuotrauka ir kontaktai matomi vusa.lt.',
    ],
    'name_locked' => 'Vardą ir pavardę gali keisti tik sistemos administratoriai arba naudotojas, vieną kartą.',
    'identity_locked_tenants' => 'El. pašto keisti negali: šis asmuo priklauso ir kitiems padaliniams (:tenants). Kreipkis į vusa.lt pagalbą.',
    'identity_locked_protected' => 'El. pašto keisti negali: šios paskyros rolė priskirta tiesiogiai. Kreipkis į centrinio biuro administratorius.',
    'identity_locked_hint' => 'El. paštas naudojamas prisijungimui, todėl jį gali keisti tik administratoriai, kuriems priklauso visi šio asmens padaliniai.',
    'duty_outside_tenant' => 'Negali priskirti ar pašalinti pareigybių, kurios priklauso kitiems padaliniams: :duties.',
    'cannot_delete_self' => 'Savo paskyros ištrinti negali.',
    'duplicate_warning_title' => 'Panašus profilis jau egzistuoja — vienam asmeniui turėtų būti tik viena paskyra.',
    'duplicate_reason_email' => 'toks pat el. paštas',
    'duplicate_use_profile' => 'Naudoti šį profilį',
    'duplicate_open_profile' => 'Peržiūrėti',
    'duplicate_contact_admins' => 'Kreipkis į to padalinio administratorius',
    'merge' => [
        'name_mismatch' => 'Sujungti galima tik to paties asmens įrašus, o :name vardas ir pavardė nesutampa.',
        'name_changed' => ':name vardas ar pavardė jau buvo pakeisti, todėl šių įrašų sujungti negalima.',
        'direct_roles' => ':name rolė priskirta tiesiogiai. Prieš sujungdamas ją pašalink.',
        'outside_scope' => 'Sujungti gali administratoriai, kuriems priklauso visi abiejų įrašų padaliniai.',
        'action' => 'Sujungti',
        'login_email' => 'Prisijungimo el. paštas',
        'login_email_hint' => 'Kiti el. paštai prisijungti nebeleis.',
        'permanent' => 'Sujungti įrašai bus ištrinti visam laikui.',
    ],
    'no_tenant' => 'Be padalinio',
    'filters' => [
        'duty_timing' => 'Pareigų pradžia',
        'scheduled' => 'Būsimos pareigos',
    ],
    'validation' => [
        'current_password_required' => 'Dabartinis slaptažodis yra privalomas.',
        'current_password_string' => 'Dabartinis slaptažodis turi būti tekstas.',
        'password_required' => 'Naujas slaptažodis yra privalomas.',
        'password_confirmed' => 'Slaptažodžio patvirtinimas nesutampa.',
        'password_confirmation_required' => 'Slaptažodžio patvirtinimas yra privalomas.',
    ],
];
