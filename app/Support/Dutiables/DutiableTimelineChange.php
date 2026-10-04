<?php

namespace App\Support\Dutiables;

use App\Actions\Dutiables\PlanDutiableTimelineChanges;

/**
 * One row's projected move, as computed by {@see PlanDutiableTimelineChanges}.
 *
 * The same object serialises into the dry-run preview and drives the write, so what the
 * user confirmed and what gets saved can never diverge.
 */
final readonly class DutiableTimelineChange
{
    /**
     * @param  array{start_date: string, end_date: string|null}  $before
     * @param  array{start_date: string, end_date: string|null}  $after
     * @param  list<string>  $reasons  the operation types that produced this move
     * @param  list<array{id: string, duty_name: string|null, use_original_duty_name: bool, start_date: string, end_date: string|null}>  $derived
     * @param  array<string, string>  $holderPronouns
     * @param  string|null  $blocked  reason code; a blocked change is previewed but never written
     */
    public function __construct(
        public string $rowId,
        public string $holderId,
        public ?string $holderName,
        public ?string $dutyName,
        public array $before,
        public array $after,
        public array $reasons,
        public array $derived = [],
        public ?string $blocked = null,
        public array $holderPronouns = [],
        public bool $useOriginalDutyName = false,
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
            'holder_pronouns' => $this->holderPronouns,
            'use_original_duty_name' => $this->useOriginalDutyName,
            'duty_name' => $this->dutyName,
            'before' => $this->before,
            'after' => $this->after,
            'reasons' => $this->reasons,
            'derived' => $this->derived,
            'blocked' => $this->blocked,
        ];
    }
}
