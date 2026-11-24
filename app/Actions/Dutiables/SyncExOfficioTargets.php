<?php

namespace App\Actions\Dutiables;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Services\Duties\DutyAssignmentService;
use App\Support\Dutiables\DutyAssignmentActor;
use Illuminate\Support\Facades\DB;

class SyncExOfficioTargets
{
    public static function execute(Duty $source, array $targetIds): void
    {
        DB::transaction(function () use ($source, $targetIds): void {
            $assignments = app(DutyAssignmentService::class);
            $targetIds = array_values(array_unique(array_filter($targetIds, fn ($id) => $id !== $source->id)));
            $duties = Duty::query()->whereKey([...$targetIds, $source->id])
                ->orWhereHas('exOfficioTargetDuties', fn ($query) => $query->whereKey($source->id))->get();
            $assignments->lockDuties($duties);
            $changes = $source->syncAudited('exOfficioTargetDuties', $targetIds);
            $source->unsetRelation('exOfficioTargetDuties');
            if ($changes['detached'] !== []) {
                $targets = Duty::withTrashed()->whereKey($changes['detached'])->get()->keyBy('id');
                $seats = Dutiable::query()->whereIn('duty_id', $changes['detached'])
                    ->whereHas('viaDutiable', fn ($query) => $query->where('duty_id', $source->id))
                    ->where('ended_by_institution_deactivation', false)->notEnded()
                    ->with('viaDutiable')->orderBy('id')->lockForUpdate()->get()
                    ->filter(function (Dutiable $seat) use ($assignments, $targets): bool {
                        $target = $targets->get($seat->duty_id);

                        return $target !== null && $assignments->lockExOfficioContext($seat->viaDutiable, $target);
                    });

                foreach ($seats->groupBy('duty_id') as $dutyId => $terms) {
                    $targets[$dutyId]->auditRelationChange('current_or_upcoming_users', fn () => DutyAssignmentActor::system(
                        fn () => $assignments->endTerms(Dutiable::whereKey($terms->modelKeys())),
                    ));
                }
            }
            if ($changes['attached'] !== [] || $changes['detached'] !== [] || $changes['updated'] !== []) {
                ReconcileExOfficioSeats::forDuty($source);
            }
        });
    }
}
