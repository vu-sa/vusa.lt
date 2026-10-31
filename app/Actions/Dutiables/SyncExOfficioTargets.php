<?php

namespace App\Actions\Dutiables;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Services\DutyAssignmentService;
use Illuminate\Support\Facades\DB;

class SyncExOfficioTargets
{
    public static function execute(Duty $source, array $targetIds): void
    {
        DB::transaction(function () use ($source, $targetIds): void {
            Duty::query()->whereKey($source->id)->lockForUpdate()->firstOrFail();
            $targetIds = array_values(array_unique(array_filter($targetIds, fn ($id) => $id !== $source->id)));
            $changes = $source->syncAudited('exOfficioTargetDuties', $targetIds);
            $source->unsetRelation('exOfficioTargetDuties');
            if ($changes['detached'] !== []) {
                $targets = Duty::withTrashed()->whereKey($changes['detached'])->get()->keyBy('id');
                Dutiable::query()->whereIn('duty_id', $changes['detached'])
                    ->whereHas('viaDutiable', fn ($query) => $query->where('duty_id', $source->id))
                    ->where('ended_by_institution_deactivation', false)->current()
                    ->with('viaDutiable')->orderBy('id')->lockForUpdate()->get()
                    ->each(function (Dutiable $seat) use ($targets): void {
                        $target = $targets->get($seat->duty_id);
                        if ($target === null || ! app(DutyAssignmentService::class)->lockExOfficioContext($seat->viaDutiable, $target)) {
                            return;
                        }
                        if ($seat->start_date->gt(today())) {
                            $seat->delete();
                        } else {
                            $seat->update(['end_date' => today()->toDateString()]);
                        }
                    });
            }
            if ($changes['attached'] !== [] || $changes['detached'] !== [] || $changes['updated'] !== []) {
                ReconcileExOfficioSeats::forDuty($source);
            }
        });
    }
}
