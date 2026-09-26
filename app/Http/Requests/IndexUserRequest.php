<?php

namespace App\Http\Requests;

class IndexUserRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'name', 'desc' => false],
    ];

    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'future_duty' => 'nullable|in:scheduled',
        ];
    }
}
