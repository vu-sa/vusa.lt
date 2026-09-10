<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class IndexSupportRequestRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'created_at', 'desc' => true],
    ];

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'tab' => ['nullable', Rule::in(['all', 'mine'])],
        ];
    }
}
