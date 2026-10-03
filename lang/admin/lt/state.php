<?php

return [
    'status' => [
        'created' => 'pateikta',
        'reserved' => 'rezervuota',
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
            'created' => 'Daikto rezervacijos užklausa pateikta! Laukiama, kol išteklių administratoriai patvirtins rezervaciją.',
            'cancelled' => 'Išteklio rezervacija atšaukta.',
            'lent' => 'Daiktas sėkmingai paskolintas išteklio savininkų ir įpareigotas grąžinti nurodytu laiku.',
            'rejected' => 'Išteklio rezervacija atmesta. Dėl atmetimo priežasčių pasižiūrėk komentarų skiltį arba susisiek su išteklio administratoriais.',
            'reserved' => 'Išteklius rezervuotas! Rezervuotą išteklių atsiimk nurodytu laiku.',
            'returned' => 'Išteklio grąžinimas sėkmingas.',
        ],
    ],
    'comment' => [
        'lent' => 'pažymėti, kaip paskolintą',
        'return' => 'pažymėti, kaip grąžintą',
    ],
];
