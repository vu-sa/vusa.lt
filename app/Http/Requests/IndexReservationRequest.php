<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class IndexReservationRequest extends BaseIndexRequest
{
    /** Item states a reservation can be filtered by (`reservation_resource.state`). */
    public const array STATES = ['created', 'reserved', 'lent', 'returned', 'rejected', 'cancelled'];

    public const array SCOPES = ['mine', 'administered'];

    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'start_time', 'desc' => true],
    ];

    /**
     * The page URL carries `state=created,lent`; the API receives `state[]=created`. Both mean the same.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('state'))) {
            $this->merge(['state' => array_values(array_filter(explode(',', $this->input('state'))))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'scope' => ['nullable', Rule::in(self::SCOPES)],
            'state' => ['nullable', 'array'],
            'state.*' => ['string', Rule::in(self::STATES)],
            'overdue' => ['nullable', 'boolean'],
        ];
    }

    public function getScope(): ?string
    {
        return $this->validated('scope');
    }

    /**
     * @return list<string>
     */
    public function getStates(): array
    {
        return $this->validated('state') ?? [];
    }

    public function getOverdue(): bool
    {
        return (bool) $this->validated('overdue');
    }
}
