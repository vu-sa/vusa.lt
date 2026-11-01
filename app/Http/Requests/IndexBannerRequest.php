<?php

namespace App\Http\Requests;

class IndexBannerRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'is_active', 'desc' => true],
    ];
}
