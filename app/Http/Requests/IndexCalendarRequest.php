<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class IndexCalendarRequest extends BaseIndexRequest
{
    /** @var array<int, array{id: string, desc: bool}> */
    #[\Override]
    protected array $defaultSorting = [
        ['id' => 'date', 'desc' => true],
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'untyped' => 'nullable|string|in:true,false',
        ];
    }

    /**
     * The backfill review queue: events left without an event type. A dedicated query
     * param rather than a Tanstack column filter, since "no type" has no value to filter
     * by — mirrors `getShowDeleted()`.
     */
    public function getUntyped(): bool
    {
        return $this->boolean('untyped');
    }
}
