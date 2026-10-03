<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\User;
use App\Services\DutyAssignmentService;
use App\Support\MorphMap;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateDuty
{
    public function __construct(private DutyAssignmentService $assignments) {}

    /**
     * @param array{name: array<string, string|null>|string, institution_id: string, places_to_occupy: int|float|string, contacts_grouping: string,
     *     description?: array<string, string|null>|string|null, email?: string|null, current_users?: list<string>|null, roles?: list<string>|null,
     *     types?: list<int>|null, ex_officio_target_duty_ids?: list<string>|null, assignable_tenants?: list<array{tenant_id: int, quota?: int|null, user_ids?: list<string>|null}>|null} $data
     */
    public function execute(Duty $duty, array $data): void
    {
        DB::transaction(function () use ($duty, $data): void {
            $duty->update(Arr::only($data, ['name', 'description', 'email', 'places_to_occupy', 'contacts_grouping']));

            if (isset($data['current_users'])) {
                $this->assignments->syncRepresentatives($duty, $data['current_users']);
            }

            $duty->institution()->disassociate();
            $duty->institution()->associate($data['institution_id']);
            $duty->save();

            $roles = Role::find($data['roles'] ?? []);
            foreach ($roles as $role) {
                if ($role->name === config('permission.super_admin_role_name')) {
                    abort(403, __('messages.role.not_assignable_to_duty'));
                }
            }

            $duty->syncRoles($roles);
            $duty->types()->sync($data['types'] ?? []);

            $previousTargetIds = $duty->exOfficioTargetDuties()->pluck('duties.id')->all();
            $newTargetIds = array_filter($data['ex_officio_target_duty_ids'] ?? []);
            $duty->exOfficioTargetDuties()->sync($newTargetIds);

            $addedTargetIds = array_values(array_diff($newTargetIds, $previousTargetIds));
            $removedTargetIds = array_values(array_diff($previousTargetIds, $newTargetIds));

            if ($addedTargetIds || $removedTargetIds) {
                $dutyId = $duty->id;
                dispatch(function () use ($dutyId, $addedTargetIds, $removedTargetIds): void {
                    $duty = Duty::find($dutyId);
                    if ($duty) {
                        BackfillExOfficioTargetDuty::execute($duty, $addedTargetIds, $removedTargetIds);
                    }
                })->afterCommit();
            }

            $previousTenantIds = $duty->assignableTenants()->pluck('tenants.id')->all();
            $assignableTenantsInput = $data['assignable_tenants'] ?? [];
            $newTenantIds = array_column($assignableTenantsInput, 'tenant_id');

            foreach (array_diff($previousTenantIds, $newTenantIds) as $tenantId) {
                $this->assignments->endDateDutiables(
                    Dutiable::query()
                        ->where('duty_id', $duty->id)
                        ->where('dutiable_type', MorphMap::alias(User::class))
                        ->where('tenant_id', $tenantId)
                        ->current(),
                    now()->subDay()
                );
            }

            $duty->assignableTenants()->sync($this->assignments->buildAssignableTenantsSync($assignableTenantsInput));

            foreach ($assignableTenantsInput as $row) {
                $this->assignments->syncRepresentatives($duty, $row['user_ids'] ?? [], (int) $row['tenant_id']);
            }
        });
    }
}
