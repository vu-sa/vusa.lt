<?php

namespace App\Http\Requests;

class IndexUserRequest extends BaseIndexRequest
{
    #[\Override]
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $filters = $this->getFilters();

        if (array_key_exists('duplicates', $filters)) {
            $this->merge(['duplicates' => $filters['duplicates']]);
        }
    }

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
            'membership' => 'nullable|in:current,former',
            'duplicates' => 'nullable|in:possible',
        ];
    }
}
