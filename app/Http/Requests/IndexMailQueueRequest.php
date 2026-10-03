<?php

namespace App\Http\Requests;

class IndexMailQueueRequest extends BaseIndexRequest
{
    #[\Override]
    protected int $defaultPerPage = 25;

    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'items_count', 'desc' => true],
    ];
}
