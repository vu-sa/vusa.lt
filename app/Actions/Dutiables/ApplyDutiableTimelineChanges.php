<?php

namespace App\Actions\Dutiables;

use App\Models\Duty;
use App\Models\User;
use App\Services\Duties\DutyAssignmentService;
use App\Support\Dutiables\DutiableTimelineChange;
use App\Support\Dutiables\DutiableTimelinePlan;
use App\Support\Dutiables\DutyAssignmentActor;

class ApplyDutiableTimelineChanges
{
    public static function execute(DutiableTimelinePlan $plan, User $actor): void
    {
        $assignments = app(DutyAssignmentService::class);
        $rows = collect($plan->writableChanges())->map(fn (DutiableTimelineChange $change) => $plan->rows->get($change->rowId));
        $dutyIds = $rows->pluck('duty_id')->unique()->all();
        $duties = Duty::query()->whereKey($dutyIds)->with('exOfficioTargetDuties')->orderBy('id')->get();
        $affectedDuties = $duties->concat($duties->flatMap->exOfficioTargetDuties);

        $members = User::whereKey($rows->pluck('dutiable_id')->unique()->all())->get();
        $assignments->auditMemberships($affectedDuties, $members,
            fn () => DutyAssignmentActor::as($actor, fn () => self::writeChanges($plan, $actor, $assignments)));
    }

    private static function writeChanges(DutiableTimelinePlan $plan, User $actor, DutyAssignmentService $assignments): void
    {
        $today = today()->toDateString();
        $changes = collect($plan->writableChanges())
            ->sortBy(fn (DutiableTimelineChange $change) => ($change->after['end_date'] ?? $today) >= $today);
        foreach ($changes as $change) {
            abort_unless($actor->can('manageDutiable', $plan->rows->get($change->rowId)), 403);
        }

        $changedRows = [];
        $quotaRows = [];
        foreach ($changes as $change) {
            $row = $plan->rows->get($change->rowId);
            if ($assignments->allocatesSeat($row, $change->after['end_date'], $row->tenant_id)) {
                $quotaRows[$row->duty_id][$row->tenant_id] = $row;
            }

            $row->fill($change->after)->save();
            $changedRows[] = $row;
        }

        // Validate the final periods so rows in the same save can move out of each other's way.
        foreach ($changedRows as $row) {
            $assignments->validateTermEdit($row, null, $row->end_date?->toDateString(), $row->tenant_id, 'operations');
        }
        foreach ($quotaRows as $tenants) {
            foreach ($tenants as $row) {
                $assignments->enforceTenantQuota($row->duty, $row->tenant_id, $actor, 'operations');
            }
        }
    }
}
