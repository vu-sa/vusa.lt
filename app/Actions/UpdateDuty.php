<?php

namespace App\Actions;

use App\Actions\Dutiables\ReconcileExOfficioSeats;
use App\Actions\Dutiables\SyncExOfficioTargets;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Rules\SoftDeleteRules;
use App\Rules\WithinAuthorizedTenantScope;
use App\Services\DutyAssignmentService;
use App\Services\ModelAuthorizer;
use App\Support\MorphMap;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateDuty
{
    public function __construct(private readonly DutyAssignmentService $assignments) {}

    /**
     * @param array{name: array<string, string|null>|string, institution_id: string, places_to_occupy?: int|string|null, contacts_grouping: string,
     *     description?: array<string, string|null>|string|null, email?: string|null, current_users?: list<string>|null,
     *     types?: list<int>|null, ex_officio_target_duty_ids?: list<string>|null, assignable_tenants?: list<array{tenant_id: int, quota?: int|null, user_ids?: list<string>|null}>|null,
     *     is_active?: bool, former_names?: list<array{name: array<string, string|null>, used_until: string}>|null} $data
     */
    public function execute(Duty $duty, array $data, ?User $actor = null): void
    {
        DB::transaction(function () use ($duty, $data, $actor): void {
            Institution::withTrashed()->whereIn('id', [$duty->institution_id, $data['institution_id']])
                ->orderBy('id')->lockForUpdate()->get();
            $institutionChanged = $duty->institution_id !== $data['institution_id'];
            if ($institutionChanged && $actor === null) {
                throw ValidationException::withMessages(['institution_id' => __('validation.outside_tenant_scope')]);
            }
            Validator::make($data, [
                'institution_id' => [
                    'bail', 'required', 'ulid', SoftDeleteRules::existsLive('institutions'),
                    ...($institutionChanged ? [new WithinAuthorizedTenantScope(Institution::class, 'duties.update.padalinys', $actor)] : []),
                ],
            ])->validate();

            $authorizer = app(ModelAuthorizer::class);
            $editableTenantIds = $actor !== null && ! $authorizer->allows($actor, 'duties.update.*')
                ? $authorizer->tenants($actor, 'duties.update.padalinys')->modelKeys()
                : null;

            $duty->update(Arr::only($data, ['name', 'description', 'email', 'places_to_occupy', 'contacts_grouping', 'is_active']));

            if (array_key_exists('former_names', $data)) {
                $this->syncFormerNames($duty, $data['former_names'] ?? []);
            }

            $duty->institution()->disassociate();
            $duty->institution()->associate($data['institution_id']);
            $duty->save();

            $duty->syncAudited('types', [
                ...($data['types'] ?? []),
                ...$duty->types()->onlyTrashed()->pluck('duty_types.id')->all(),
            ]);

            if (isset($data['current_users'])) {
                $this->assignments->syncRepresentatives($duty, $data['current_users'], actor: $actor);
            }

            SyncExOfficioTargets::execute($duty, array_values(array_filter($data['ex_officio_target_duty_ids'] ?? [])));

            $tenantIdsChanged = $this->syncAssignableTenants($duty, $data['assignable_tenants'] ?? [], $editableTenantIds, $actor);

            if ($institutionChanged || $tenantIdsChanged) {
                ReconcileExOfficioSeats::forDuty($duty);
            }
        });
    }

    /**
     * @param  list<array{tenant_id: int|string, quota?: int|string|null, user_ids?: list<string>|null}>  $rows
     * @param  list<int>|null  $editableTenantIds
     */
    private function syncAssignableTenants(Duty $duty, array $rows, ?array $editableTenantIds, ?User $actor): bool
    {
        $assignableTenants = $duty->assignableTenants();
        $previousTenantIds = $assignableTenants->pluck('tenants.id')->all();

        if ($editableTenantIds !== null) {
            $previousTenantIds = array_intersect($previousTenantIds, $editableTenantIds);
            $rows = array_values(array_filter($rows, fn (array $row) => in_array((int) $row['tenant_id'], $editableTenantIds, true)));
        }

        $newTenantIds = array_column($rows, 'tenant_id');
        $removedTenantIds = array_diff($previousTenantIds, $newTenantIds);

        foreach ($removedTenantIds as $tenantId) {
            $this->assignments->endDateDutiables(
                Dutiable::query()
                    ->where('duty_id', $duty->id)
                    ->where('dutiable_type', MorphMap::alias(User::class))
                    ->where('tenant_id', $tenantId)
                    ->whereNull('via_dutiable_id')
                    ->notEnded(),
                now()->subDay()
            );
        }

        $assignableTenants->detach($removedTenantIds);
        $assignableTenants->syncWithoutDetaching($this->assignments->buildAssignableTenantsSync($rows));

        // A missing list must preserve the existing representatives.
        foreach ($rows as $row) {
            if (array_key_exists('user_ids', $row)) {
                $this->assignments->syncRepresentatives($duty, $row['user_ids'] ?? [], (int) $row['tenant_id'], $actor);
            }
        }

        return $removedTenantIds !== [] || array_diff($newTenantIds, $previousTenantIds) !== [];
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
