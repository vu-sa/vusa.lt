<?php

namespace App\Actions;

use App\Listeners\SyncExOfficioDutiables;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
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
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereNull('via_dutiable_id')
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
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
                if ($targetDuty !== null) {
                    app(SyncExOfficioDutiables::class)->syncDerivedRow($source, $source->dutiable_id, $targetDuty);
                }
            }
        }

        foreach ($removedTargetDutyIds as $targetDutyId) {
            if ($sourceDuty->exOfficioTargetDuties()->whereKey($targetDutyId)->exists()) {
                continue;
            }
            $sourceIds = Dutiable::query()->where('duty_id', $sourceDuty->id)->whereNull('via_dutiable_id')->pluck('id');
            foreach (Dutiable::query()->whereIn('via_dutiable_id', $sourceIds)->where('duty_id', $targetDutyId)
                ->where('ended_by_institution_deactivation', false)->current()->get() as $seat) {
                if ($seat->start_date->gt(today())) {
                    $seat->delete();
                } else {
                    $seat->update(['end_date' => today()]);
                }
            }
        }
    }
}
