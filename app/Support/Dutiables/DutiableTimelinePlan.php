<?php

namespace App\Support\Dutiables;

use App\Models\Pivots\Dutiable;
use Illuminate\Support\Collection;

/**
 * The full result of folding a timeline operation list over the rows it targets.
 *
 * One planner, two consumers: the preview endpoint serialises this, the apply endpoint
 * executes it. The client's own diff is advisory and never trusted.
 */
final readonly class DutiableTimelinePlan
{
    /**
     * @param  list<DutiableTimelineChange>  $changes
     * @param  list<string>  $unchangedRowIds
     * @param  Collection<string, Dutiable>  $rows  keyed by id, for the applier
     * @param  list<array<string, mixed>>  $diagnosticsBefore
     * @param  list<array<string, mixed>>  $diagnosticsAfter
     */
    public function __construct(
        public array $changes,
        public array $unchangedRowIds,
        public Collection $rows,
        public array $diagnosticsBefore = [],
        public array $diagnosticsAfter = [],
    ) {}

    /**
     * @return list<DutiableTimelineChange>
     */
    public function writableChanges(): array
    {
        return array_values(array_filter($this->changes, fn (DutiableTimelineChange $change) => ! $change->isBlocked()));
    }

    /**
     * @return list<DutiableTimelineChange>
     */
    public function blockedChanges(): array
    {
        return array_values(array_filter($this->changes, fn (DutiableTimelineChange $change) => $change->isBlocked()));
    }

    /**
     * Whether the acting user's own assignments are among the rows that will be written —
     * the trigger for AdminController::guardSelfLockout().
     */
    public function touchesUser(string $userId): bool
    {
        return array_any($this->writableChanges(), fn ($change) => $change->holderId === $userId);
    }

    /**
     * A short "n rows were left alone, and why" line for the success flash. Null when
     * everything went through, so the caller can drop the key entirely.
     */
    public function blockedMessage(): ?string
    {
        $blocked = $this->blockedChanges();

        if ($blocked === []) {
            return null;
        }

        return trans_choice('dutiables.timeline.blocked_summary', count($blocked), ['count' => count($blocked)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $writable = $this->writableChanges();

        return [
            'changes' => array_map(fn (DutiableTimelineChange $change) => $change->toArray(), $this->changes),
            'unchanged_row_ids' => $this->unchangedRowIds,
            'diagnostics_before' => $this->diagnosticsBefore,
            'diagnostics_after' => $this->diagnosticsAfter,
            'summary' => [
                'changed' => count($writable),
                'blocked' => count($this->blockedChanges()),
                'unchanged' => count($this->unchangedRowIds),
                'derived' => array_sum(array_map(fn (DutiableTimelineChange $change) => count($change->derived), $writable)),
            ],
        ];
    }
}
