<?php

namespace App\Http\Requests;

class IndexReservationRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    protected array $defaultSorting = [
        ['id' => 'start_time', 'desc' => true],
    ];
}
