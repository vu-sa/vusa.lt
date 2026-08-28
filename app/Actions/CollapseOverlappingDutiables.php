<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;

/**
 * Folds overlapping dutiable rows for a single duty into one row per holder.
 *
 * A "holder" is a (dutiable_type, dutiable_id) pair — the same person can carry
 * several rows on one duty after a merge (duties or users) leaves two stints
 * side by side. For each holder, walk its start_date-sorted rows and fold every
 * row that overlaps the running interval into it — keeping the earliest start,
 * the latest (or open-ended) end, and backfilling any nullable field the
 * survivor is missing from the row being dropped.
 *
 * Genuinely separate (non-overlapping) stints are left untouched: a person who
 * held a duty in 2020 and again in 2023 keeps two rows. Rows under different
 * tenant scopes (owning-tenant vs. a cross-tenant rep assignment) are treated
 * as distinct holders — the same person can legitimately carry both, so they
 * are never folded into one another.
 *
 * Extracted from {@see MergeDuties} so both duty- and user-merges collapse
 * consistently, and so the same logic can clean up duplicate active rows
 * created by other paths.
 */
class CollapseOverlappingDutiables
{
    /**
     * Collapse every set of overlapping rows on the given duty.
     *
     * @return int The number of redundant rows removed.
     */
    public static function execute(Duty $kept): int
    {
        $collapsed = 0;

        $rows = Dutiable::query()
            ->where('duty_id', $kept->id)
            ->orderBy('dutiable_type')
            ->orderBy('dutiable_id')
            ->orderBy('tenant_id')
            ->orderBy('start_date')
            ->get();

        foreach ($rows->groupBy(fn (Dutiable $row) => $row->dutiable_type.'|'.$row->dutiable_id.'|'.$row->tenant_id) as $group) {
            /** @var Dutiable|null $survivor */
            $survivor = null;

            foreach ($group as $row) {
                if ($survivor === null) {
                    $survivor = $row;

                    continue;
                }

                if (self::overlapsRunningInterval($survivor, $row)) {
                    self::foldInto($survivor, $row);
                    $row->delete();
                    $collapsed++;

                    continue;
                }

                $survivor = $row;
            }
        }

        return $collapsed;
    }

    /**
     * $rows arrive start_date-sorted, so $next's start is never before
     * $survivor's — it overlaps unless $survivor already ended before it began.
     */
    private static function overlapsRunningInterval(Dutiable $survivor, Dutiable $next): bool
    {
        return $survivor->end_date === null || $next->start_date->lte($survivor->end_date);
    }

    /**
     * Widen `$survivor` to also cover `$loser`, then backfill anything it is missing.
     *
     * Public because the timeline editor's explicit merge folds rows the same way — the
     * only difference there is that the user chose them rather than an overlap scan.
     */
    public static function foldInto(Dutiable $survivor, Dutiable $loser): void
    {
        if ($survivor->end_date !== null) {
            $survivor->end_date = $loser->end_date === null || $loser->end_date->gt($survivor->end_date)
                ? $loser->end_date
                : $survivor->end_date;
        }

        foreach (['study_program_id', 'study_program_note', 'additional_email', 'additional_photo', 'additional_photo_focal_point', 'description'] as $field) {
            if (blank($survivor->{$field}) && filled($loser->{$field})) {
                $survivor->{$field} = $loser->{$field};
            }
        }

        $survivor->save();
    }
}
