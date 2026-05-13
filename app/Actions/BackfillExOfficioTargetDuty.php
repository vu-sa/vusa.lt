<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;

/**
 * When an ex-officio target duty is added to or removed from a source duty,
 * backfill or end-date the derived Dutiable rows for all current holders.
 */
class BackfillExOfficioTargetDuty
{
    /**
     * @param  Duty  $sourceDuty  The duty that grants ex-officio seats.
     * @param  array<string>  $addedTargetDutyIds  Target duties newly linked.
     * @param  array<string>  $removedTargetDutyIds  Target duties unlinked.
     */
    public static function execute(Duty $sourceDuty, array $addedTargetDutyIds, array $removedTargetDutyIds): void
    {
        if (empty($addedTargetDutyIds) && empty($removedTargetDutyIds)) {
            return;
        }

        // Load active (source) Dutiables for this duty — root sources only.
        $sourceRows = Dutiable::where('duty_id', $sourceDuty->id)
            ->where('dutiable_type', User::class)
            ->whereNull('via_dutiable_id')
            ->whereNull('end_date')
            ->get();

        // Add derived rows for newly-linked targets.
        foreach ($addedTargetDutyIds as $targetDutyId) {
            foreach ($sourceRows as $source) {
                self::createOrAdopt($source, $targetDutyId);
            }
        }

        // End-date derived rows for removed targets (keep history).
        foreach ($removedTargetDutyIds as $targetDutyId) {
            foreach ($sourceRows as $source) {
                Dutiable::where('via_dutiable_id', $source->id)
                    ->where('duty_id', $targetDutyId)
                    ->whereNull('end_date')
                    ->update(['end_date' => now()]);
            }
        }
    }

    private static function createOrAdopt(Dutiable $source, string $targetDutyId): void
    {
        $userId = $source->dutiable_id;

        $existing = Dutiable::where('via_dutiable_id', $source->id)
            ->where('duty_id', $targetDutyId)
            ->where('dutiable_type', User::class)
            ->where('dutiable_id', $userId)
            ->first();

        if ($existing) {
            return;
        }

        // Adopt an existing active manual row.
        $manual = Dutiable::where('duty_id', $targetDutyId)
            ->where('dutiable_type', User::class)
            ->where('dutiable_id', $userId)
            ->whereNull('via_dutiable_id')
            ->whereNull('end_date')
            ->first();

        if ($manual) {
            $manual->via_dutiable_id = $source->id;
            $manual->start_date = $source->start_date;
            $manual->end_date = $source->end_date;
            $manual->save();

            return;
        }

        Dutiable::create([
            'duty_id' => $targetDutyId,
            'dutiable_id' => $userId,
            'dutiable_type' => User::class,
            'via_dutiable_id' => $source->id,
            'start_date' => $source->start_date,
            'end_date' => $source->end_date,
        ]);
    }
}
