<?php

namespace App\Http\Requests;

class IndexRoleRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    protected array $defaultSorting = [
        ['id' => 'created_at', 'desc' => true],
    ];
}
