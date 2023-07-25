<?php

return [
    'status' => [
        'created' => 'sukurta',
        'reserved' => 'reservuota',
        'lent' => 'paskolinta',
        'returned' => 'grąžinta',
        'rejected' => 'atmesta',
        'cancelled' => 'atšaukta',
    ],
    'decision' => [
        'approve' => 'patvirtinti',
        'reject' => 'atmesti',
        'cancel' => 'atšaukti',
    ],
    'description' => [
        'reservation_resource' => [
            'created' => 'Daikto rezervacijos užklausa yra sukurta! Laukiama, kol išteklių administratoriai patvirtins rezervaciją.',
            'cancelled' => 'Išteklio rezervacija atšaukta.',
            'lent' => 'Daiktas sėkmingai paskolintas išteklio savininkų ir įpareigotas grąžinti nurodytu laiku.',
            'rejected' => 'Išteklio rezervacija atmesta. Dėl atmetimo priežasčių pasižiūrėkite komentarų skiltį arba susisiekite su išteklio administratoriais.',
            'reserved' => 'Išteklius rezervuotas! Rezervuotą išteklių atsiimkite nurodytu laiku.',
            'returned' => 'Išteklio grąžinimas sėkmingas.',
        ],
    ],
    'comment' => [
        'lent' => 'pažymėti, kaip paskolintą',
        'return' => 'pažymėti, kaip grąžintą',
    ],
    'other' => [
        'and_decision' => 'ir :decision',
    ],
];
