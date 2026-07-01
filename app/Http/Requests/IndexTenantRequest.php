<?php

namespace App\Http\Requests;

class IndexTenantRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    protected array $defaultSorting = [
        ['id' => 'fullname', 'desc' => false],
    ];
}
