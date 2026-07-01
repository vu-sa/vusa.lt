<?php

namespace App\Http\Requests;

class IndexNewsRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    protected array $defaultSorting = [
        ['id' => 'publish_time', 'desc' => true],
    ];
}
