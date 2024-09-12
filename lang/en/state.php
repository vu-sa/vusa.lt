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
    'description' => [
        'reservation_resource' => [
            'created' => 'The reservation request is created! Waiting for the resource administrators to approve the reservation.',
            'cancelled' => 'The reservation of the resource is cancelled.',
            'lent' => 'The resource is successfully lent to the resource owners and is obliged to return it on time.',
            'rejected' => 'The reservation of the resource is rejected. For the reasons of rejection, see the comments section or contact the resource administrators.',
            'reserved' => 'The resource is reserved! Pick up the reserved resource at the specified time.',
            'returned' => 'The resource is successfully returned.',
        ],
    ],
    'comment' => [
        'lent' => 'mark as lent',
        'return' => 'mark as returned',
    ],
];
