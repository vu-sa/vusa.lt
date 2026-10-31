<?php

namespace App\Actions;

use App\Actions\Dutiables\ReconcileExOfficioSeats;
use App\Actions\Dutiables\SyncExOfficioTargets;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\DutyAssignmentService;
use App\Support\MorphMap;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateDuty
{
    public function __construct(private readonly DutyAssignmentService $assignments) {}

    /**
     * @param array{name: array<string, string|null>|string, institution_id: string, places_to_occupy?: int|string|null, contacts_grouping: string,
     *     description?: array<string, string|null>|string|null, email?: string|null, current_users?: list<string>|null,
     *     types?: list<int>|null, ex_officio_target_duty_ids?: list<string>|null, assignable_tenants?: list<array{tenant_id: int, quota?: int|null, user_ids?: list<string>|null}>|null,
     *     is_active?: bool, former_names?: list<array{name: array<string, string|null>, used_until: string}>|null} $data
     */
    public function execute(Duty $duty, array $data): void
    {
        DB::transaction(function () use ($duty, $data): void {
            Institution::withTrashed()->whereIn('id', [$duty->institution_id, $data['institution_id']])
                ->orderBy('id')->lockForUpdate()->get();
            $institutionChanged = $duty->institution_id !== $data['institution_id'];
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

            $duty->syncAudited('types', [
                ...($data['types'] ?? []),
                ...$duty->types()->onlyTrashed()->pluck('duty_types.id')->all(),
            ]);

            SyncExOfficioTargets::execute($duty, array_values(array_filter($data['ex_officio_target_duty_ids'] ?? [])));

            $previousTenantIds = $duty->assignableTenants()->pluck('tenants.id')->all();
            $assignableTenantsInput = $data['assignable_tenants'] ?? [];
            $newTenantIds = array_column($assignableTenantsInput, 'tenant_id');
            $tenantIdsChanged = array_diff($previousTenantIds, $newTenantIds) !== []
                || array_diff($newTenantIds, $previousTenantIds) !== [];

            foreach (array_diff($previousTenantIds, $newTenantIds) as $tenantId) {
                $this->assignments->endDateDutiables(
                    Dutiable::query()
                        ->where('duty_id', $duty->id)
                        ->where('dutiable_type', MorphMap::alias(User::class))
                        ->where('tenant_id', $tenantId)
                        ->whereNull('via_dutiable_id')
                        ->current(),
                    now()->subDay()
                );
            }

            $duty->assignableTenants()->sync($this->assignments->buildAssignableTenantsSync($assignableTenantsInput));

            // The duty form sends only tenant and quota; a missing list must not read as "remove everyone".
            foreach ($assignableTenantsInput as $row) {
                if (array_key_exists('user_ids', $row)) {
                    $this->assignments->syncRepresentatives($duty, $row['user_ids'] ?? [], (int) $row['tenant_id']);
                }
            }
            if ($institutionChanged || $tenantIdsChanged) {
                ReconcileExOfficioSeats::forDuty($duty);
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
