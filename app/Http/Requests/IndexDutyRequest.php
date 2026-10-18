<?php

namespace App\Http\Requests;

use App\Models\Duty;

class IndexDutyRequest extends BaseIndexRequest
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
            'merge' => 'nullable|boolean',
            'source' => 'nullable|ulid|required_with:target',
            'target' => 'nullable|ulid|different:source',
        ];
    }

    /**
     * The duplicate warning's "merge into this one" link: both duties, kept one first, so the
     * list can open the merge dialog. Null unless the actor may merge and both duties are live.
     *
     * @return array{target_id: string, duties: list<array<string, mixed>>}|null
     */
    public function mergePreset(): ?array
    {
        $sourceId = $this->validated('source');
        $targetId = $this->validated('target');

        if (! $this->boolean('merge') || ! $sourceId || ! $targetId || ! $this->user()->can('merge', Duty::class)) {
            return null;
        }

        $duties = Duty::query()->with('institution')->whereKey([$sourceId, $targetId])->get();

        if ($duties->count() !== 2) {
            return null;
        }

        return [
            'target_id' => $targetId,
            'duties' => $duties->sortByDesc(fn (Duty $duty) => $duty->id === $targetId)
                ->map(fn (Duty $duty) => $duty->toFullArray())
                ->values()
                ->all(),
        ];
    }
}
