<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\User;
use App\Observers\UserPermissionObserver;
use App\Services\DutyAssignmentService;
use App\Support\MorphMap;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateDuty
{
    public function __construct(private readonly DutyAssignmentService $assignments) {}

    /**
     * @param array{name: array<string, string|null>|string, institution_id: string, places_to_occupy: int|float|string, contacts_grouping: string,
     *     description?: array<string, string|null>|string|null, email?: string|null, current_users?: list<string>|null, roles?: list<string>|null,
     *     types?: list<int>|null, ex_officio_target_duty_ids?: list<string>|null, assignable_tenants?: list<array{tenant_id: int, quota?: int|null, user_ids?: list<string>|null}>|null,
     *     is_active?: bool, former_names?: list<array{name: array<string, string|null>, used_until: string}>|null} $data
     */
    public function execute(Duty $duty, array $data): void
    {
        DB::transaction(function () use ($duty, $data): void {
            Institution::withTrashed()->whereIn('id', [$duty->institution_id, $data['institution_id']])
                ->orderBy('id')->lockForUpdate()->get();
            $duty->update(Arr::only($data, ['name', 'description', 'email', 'places_to_occupy', 'contacts_grouping', 'is_active']));

            if (array_key_exists('former_names', $data)) {
                $this->syncFormerNames($duty, $data['former_names'] ?? []);
            }

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

            $roleIdsBefore = $duty->roles()->pluck('roles.id')->sort()->values()->all();
            $duty->syncRoles($roles);

            // syncRoles() fires no model event, so holders would keep the old roles in their cached duties.
            if ($duty->roles()->pluck('roles.id')->sort()->values()->all() !== $roleIdsBefore) {
                DB::afterCommit(fn () => app(UserPermissionObserver::class)->updatedDuty($duty));
            }

            $duty->syncAudited('types', $data['types'] ?? []);

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

    /**
     * The list is small and edited whole, so replacing it keeps the form the single source.
     *
     * @param  list<array{name: array<string, string|null>, used_until: string}>  $formerNames
     */
    private function syncFormerNames(Duty $duty, array $formerNames): void
    {
        $duty->formerNames()->delete();

        foreach ($formerNames as $formerName) {
            $duty->formerNames()->create([
                'name' => array_filter($formerName['name']),
                'used_until' => $formerName['used_until'],
            ]);
        }

        $duty->unsetRelation('formerNames');
    }
}
