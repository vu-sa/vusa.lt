<?php

namespace App\Actions\Dutiables;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
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
            $assignments->batchMembershipAudits(function () use ($source, $changes, $assignments): void {
                if ($changes['detached'] !== []) {
                    self::endDetachedSeats($source, $changes['detached'], $assignments);
                }
                if ($changes['attached'] !== [] || $changes['detached'] !== [] || $changes['updated'] !== []) {
                    ReconcileExOfficioSeats::forDuty($source);
                }
            });
        });
    }

    private static function endDetachedSeats(Duty $source, array $targetIds, DutyAssignmentService $assignments): void
    {
        $targets = Duty::withTrashed()->whereKey($targetIds)->get()->keyBy('id');
        $seats = Dutiable::query()->whereIn('duty_id', $targetIds)
            ->whereHas('viaDutiable', fn ($query) => $query->where('duty_id', $source->id))
            ->where('ended_by_institution_deactivation', false)->notEnded()
            ->with('viaDutiable')->orderBy('id')->lockForUpdate()->get()
            ->filter(function (Dutiable $seat) use ($assignments, $targets): bool {
                $target = $targets->get($seat->duty_id);

                return $target !== null && $assignments->lockExOfficioContext($seat->viaDutiable, $target);
            });

        $affectedDuties = $targets->only($seats->pluck('duty_id')->all())->values();
        $members = User::whereKey($seats->pluck('dutiable_id')->unique()->all())->get();
        DutyAssignmentActor::system(fn () => $assignments->auditMemberships($affectedDuties, $members,
            fn () => $assignments->endTerms(Dutiable::whereKey($seats->modelKeys())),
        ));
    }
}
