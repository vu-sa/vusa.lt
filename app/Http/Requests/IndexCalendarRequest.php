<?php

namespace App\Http\Requests;

class IndexCalendarRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    protected array $defaultSorting = [
        ['id' => 'date', 'desc' => true],
    ];
}
