<?php

namespace App\Http\Requests;

class IndexBannerRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    protected array $defaultSorting = [
        ['id' => 'title', 'desc' => false],
    ];
}
