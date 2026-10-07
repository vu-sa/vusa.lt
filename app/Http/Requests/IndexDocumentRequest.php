<?php

namespace App\Http\Requests;

class IndexDocumentRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'created_at', 'desc' => true],
    ];

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            // Links to the retired separate views, redirected to the manager view's filters.
            'queue' => ['nullable', 'string', 'in:pending,hidden,removed'],
            'browse' => ['nullable', 'string', 'in:folders'],
        ];
    }
}
