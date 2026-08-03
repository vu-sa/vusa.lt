<?php

namespace App\Http\Requests;

class IndexTrainingRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'start_time', 'desc' => true],
    ];
}
