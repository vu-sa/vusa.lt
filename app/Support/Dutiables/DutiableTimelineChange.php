<?php

namespace App\Support\Dutiables;

use App\Actions\Dutiables\PlanDutiableTimelineChanges;

/**
 * One row's projected move, as computed by {@see PlanDutiableTimelineChanges}.
 *
 * The same object serialises into the dry-run preview and drives the write, so what the
 * user confirmed and what gets saved can never diverge.
 */
final class DutiableTimelineChange
{
    /**
     * @param  array{start_date: string, end_date: string|null}  $before
     * @param  array{start_date: string, end_date: string|null}  $after
     * @param  list<string>  $reasons  the operation types that produced this move
     * @param  list<array{id: string, duty_name: string|null, start_date: string, end_date: string|null}>  $derived
     * @param  string|null  $blocked  reason code; a blocked change is previewed but never written
     */
    public function __construct(
        public readonly string $rowId,
        public readonly string $holderId,
        public readonly ?string $holderName,
        public readonly ?string $dutyName,
        public readonly array $before,
        public readonly array $after,
        public readonly array $reasons,
        public readonly array $derived = [],
        public readonly ?string $blocked = null,
    ) {}

    public function isBlocked(): bool
    {
        return $this->blocked !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'row_id' => $this->rowId,
            'holder_id' => $this->holderId,
            'holder_name' => $this->holderName,
            'duty_name' => $this->dutyName,
            'before' => $this->before,
            'after' => $this->after,
            'reasons' => $this->reasons,
            'derived' => $this->derived,
            'blocked' => $this->blocked,
        ];
    }
}
