<?php

namespace App\Http\Requests;

class IndexEventTypeRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'sort_order', 'desc' => false],
    ];
}
