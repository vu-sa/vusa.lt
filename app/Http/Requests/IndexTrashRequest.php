<?php

namespace App\Http\Requests;

class IndexTrashRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'deleted_at', 'desc' => true],
    ];

    #[\Override]
    protected int $defaultPerPage = 50;

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'sorting' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                $decoded = is_string($value) ? json_decode($value, true) : null;

                foreach (is_array($decoded) ? $decoded : [] as $sort) {
                    if (! in_array($sort['id'] ?? null, ['deleted_at', 'created_at'], true)) {
                        $fail('The trash can only be sorted by deletion or creation date.');
                    }
                }
            }],
        ];
    }
}
