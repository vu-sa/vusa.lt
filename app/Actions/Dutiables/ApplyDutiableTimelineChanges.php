<?php

namespace App\Actions\Dutiables;

use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\DutyAssignmentService;
use App\Support\Dutiables\DutiableTimelineChange;
use App\Support\Dutiables\DutiableTimelinePlan;

/** Writes through each model so derived seats and permission caches follow its dates. */
class ApplyDutiableTimelineChanges
{
    /**
     * @return list<string> the ids actually written
     */
    public static function execute(DutiableTimelinePlan $plan, User $actor): array
    {
        $written = [];

        $assignments = app(DutyAssignmentService::class);
        $today = today()->toDateString();
        // Apply endings first so a swap can reuse a seat within the same save.
        $changes = collect($plan->writableChanges())->sortBy(fn ($change) => ($change->after['end_date'] ?? $today) >= $today);

        foreach ($changes as $change) {
            /** @var Dutiable|null $row */
            $row = $plan->rows->get($change->rowId);

            if ($row === null) {
                continue;
            }

            $assignments->validateSeatChange($row, $change->after['end_date'], $row->tenant_id, $actor, 'operations');
            self::write($row, $change);
            $written[] = $change->rowId;
        }

        return $written;
    }

    private static function write(Dutiable $row, DutiableTimelineChange $change): void
    {
        $row->fill([
            'start_date' => $change->after['start_date'],
            'end_date' => $change->after['end_date'],
        ])->save();
    }
}
