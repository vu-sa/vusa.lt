<?php

namespace App\Http\Requests;

class IndexUserRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    protected array $defaultSorting = [
        ['id' => 'name', 'desc' => false],
    ];
}
