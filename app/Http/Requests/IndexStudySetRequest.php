<?php

namespace App\Http\Requests;

class IndexStudySetRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    protected array $defaultSorting = [
        ['id' => 'order', 'desc' => false],
    ];
}
