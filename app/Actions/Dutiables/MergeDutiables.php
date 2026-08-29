<?php

namespace App\Actions\Dutiables;

use App\Actions\CollapseOverlappingDutiables;
use App\Models\Pivots\Dutiable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Folds several stints of one holder on one duty into a single row.
 *
 * {@see CollapseOverlappingDutiables} does this automatically for rows that overlap; this
 * is the deliberate version, for the two-rows-that-should-have-been-one case an admin can
 * see on the chart but a scan cannot prove — a gap between them is exactly why it needs a
 * human to ask for it.
 *
 * The earliest row survives so its id, and anything already pointing at it (ex-officio
 * `via_dutiable_id`), stays valid.
 */
class MergeDutiables
{
    /**
     * Rows are mergeable only within one holder's stints on one duty under one tenant —
     * the same grouping key CollapseOverlappingDutiables and the overlap diagnostic use.
     * A cross-tenant representative row is a different seat, not a duplicate.
     *
     * @param  Collection<int, Dutiable>  $rows
     */
    public static function isMergeable(Collection $rows): bool
    {
        if ($rows->count() < 2) {
            return false;
        }

        // A derived row mirrors its source; merging one would strand the source's sync.
        if ($rows->contains(fn (Dutiable $row) => $row->via_dutiable_id !== null)) {
            return false;
        }

        return $rows
            ->map(fn (Dutiable $row) => implode('|', [$row->duty_id, $row->dutiable_type, $row->dutiable_id, $row->tenant_id]))
            ->unique()
            ->count() === 1;
    }

    /**
     * @param  Collection<int, Dutiable>  $rows
     * @return Dutiable the surviving row
     */
    public static function execute(Collection $rows): Dutiable
    {
        $sorted = $rows->sortBy(fn (Dutiable $row) => $row->start_date->toDateString())->values();

        /** @var Dutiable $survivor */
        $survivor = $sorted->first();

        foreach ($sorted->skip(1) as $loser) {
            CollapseOverlappingDutiables::foldInto($survivor, $loser);
            // Deleted one at a time so DutiableChanged fires per row, exactly as the
            // timeline's own writes do — the permission cache depends on it.
            $loser->delete();
        }

        return $survivor;
    }
}
