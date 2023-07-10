<?php

return [
    'status' => [
        'created' => 'created',
        'reserved' => 'reserved',
        'lent' => 'lent',
        'returned' => 'returned',
        'rejected' => 'rejected',
        'cancelled' => 'cancelled',
    ],
    'decision' => [
        'approve' => 'approve',
        'reject' => 'reject',
        'cancel' => 'cancel',
    ],
    'comment' => [
        'lent' => 'mark as lent',
        'returned' => 'mark as returned',
    ],
    'other' => [
        'and_decision' => 'and :decision',
    ],
];
