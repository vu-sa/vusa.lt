<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use Illuminate\Support\Collection;

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

        $sourceDuty->load('institution');

        // Load active (source) Dutiables for this duty — root sources only.
        $sourceRows = Dutiable::where('duty_id', $sourceDuty->id)
            ->where('dutiable_type', User::class)
            ->whereNull('via_dutiable_id')
            ->whereNull('end_date')
            ->get();

        /** @var Collection<string, Duty> $targetDuties */
        $targetDuties = Duty::whereIn('id', $addedTargetDutyIds)
            ->with('assignableTenants')
            ->get()
            ->keyBy('id');

        // Add derived rows for newly-linked targets.
        foreach ($addedTargetDutyIds as $targetDutyId) {
            $targetDuty = $targetDuties->get($targetDutyId);

            foreach ($sourceRows as $source) {
                self::createOrAdopt($source, $targetDutyId, $targetDuty);
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

    private static function createOrAdopt(Dutiable $source, string $targetDutyId, ?Duty $targetDuty): void
    {
        $userId = $source->dutiable_id;
        $resolvedTenantId = $targetDuty ? ResolveExOfficioTenantId::execute($source, $targetDuty) : null;

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
            $manual->tenant_id = $resolvedTenantId;
            $manual->save();

            return;
        }

        Dutiable::create([
            'duty_id' => $targetDutyId,
            'dutiable_id' => $userId,
            'dutiable_type' => User::class,
            'via_dutiable_id' => $source->id,
            'tenant_id' => $resolvedTenantId,
            'start_date' => $source->start_date,
            'end_date' => $source->end_date,
        ]);
    }
}
