<?php

namespace App\Support;

use App\Enums\InstitutionActivityStatus;

/** Counts institution statuses into the buckets the trend chart and the stored snapshots share. */
final class InstitutionStatusTally
{
    /**
     * @param  iterable<InstitutionActivityStatus>  $statuses
     * @return array{all: int, needs_attention: int, overdue: int, approaching: int, no_activity: int, recent_check_in: int, current: int}
     */
    public static function fromStatuses(iterable $statuses): array
    {
        $tally = ['all' => 0, 'needs_attention' => 0, 'overdue' => 0, 'approaching' => 0, 'no_activity' => 0, 'recent_check_in' => 0];

        foreach ($statuses as $status) {
            $tally['all']++;

            match ($status) {
                InstitutionActivityStatus::Overdue => $tally['overdue']++,
                InstitutionActivityStatus::Approaching => $tally['approaching']++,
                InstitutionActivityStatus::NoActivity => $tally['no_activity']++,
                InstitutionActivityStatus::RecentCheckIn => $tally['recent_check_in']++,
                default => null,
            };

            if ($status->requiresAction()) {
                $tally['needs_attention']++;
            }
        }

        return $tally + ['current' => $tally['all'] - $tally['needs_attention'] - $tally['recent_check_in']];
    }
}
