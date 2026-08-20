<?php

namespace App\Http\Requests;

class IndexTenantRequest extends BaseIndexRequest
{
    /** Preserves the page size this listing used before getPerPage() centralised it. */
    #[\Override]
    protected int $defaultPerPage = 15;

    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'fullname', 'desc' => false],
    ];
}
