<?php

namespace App\Actions\Dutiables;

use App\Models\Pivots\Dutiable;
use App\Support\Dutiables\DutiableTimelineChange;
use App\Support\Dutiables\DutiableTimelinePlan;

/**
 * Writes an already-planned set of moves.
 *
 * Row by row, never a mass update(): each save has to fire DutiableChanged so the
 * ex-officio sync and the permission-cache invalidation both run. DutyController's
 * endDateDutiables() loops for the same reason.
 */
class ApplyDutiableTimelineChanges
{
    /**
     * @return list<string> the ids actually written
     */
    public static function execute(DutiableTimelinePlan $plan): array
    {
        $written = [];

        foreach ($plan->writableChanges() as $change) {
            /** @var Dutiable|null $row */
            $row = $plan->rows->get($change->rowId);

            if ($row === null) {
                continue;
            }

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
