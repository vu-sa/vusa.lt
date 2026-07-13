<?php

return [
    'status' => [
        'created' => 'sukurta',
        'reserved' => 'rezervuota',
        'lent' => 'paskolinta',
        'returned' => 'grąžinta',
        'rejected' => 'atmesta',
        'cancelled' => 'atšaukta',
        // Display-only: a lent resource whose return time has passed. Not a real state.
        'overdue' => 'vėluojama',
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
];
