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
use App\Services\Duties\DutyAssignmentService;
use App\Services\ModelAuthorizer;
use App\Support\Dutiables\DutyAssignmentActor;
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
            $relatedDuties = Duty::query()->whereKey($data['ex_officio_target_duty_ids'] ?? [])
                ->orWhereHas('exOfficioTargetDuties', fn ($query) => $query->whereKey($duty->id))->get();
            $this->assignments->lockDuties($relatedDuties->push($duty), [$data['institution_id']]);
            $duty->refreshForUpdate();
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

            DutyAssignmentActor::as($actor, fn () => $this->assignments->batchMembershipAudits(
                fn () => $this->syncMemberships($duty, $data, $editableTenantIds, $actor, $institutionChanged),
            ));
        });
    }

    private function syncMemberships(Duty $duty, array $data, ?array $editableTenantIds, ?User $actor, bool $institutionChanged): void
    {
        if (isset($data['current_users'])) {
            $this->assignments->syncRepresentatives($duty, $data['current_users'], actor: $actor);
        }

        $targetIds = array_values(array_filter($data['ex_officio_target_duty_ids'] ?? []));
        SyncExOfficioTargets::execute($duty, $targetIds);

        $tenantIdsChanged = $this->syncAssignableTenants($duty, $data['assignable_tenants'] ?? [], $editableTenantIds, $actor);

        if ($institutionChanged || $tenantIdsChanged) {
            ReconcileExOfficioSeats::forDuty($duty);
        }

        if (array_key_exists('is_active', $data) && ! $duty->is_active
            && $duty->dutiables()->notEnded()->lockForUpdate()->first(['id']) !== null) {
            throw ValidationException::withMessages(['is_active' => __('dutiables.assign.finish_terms_before_retiring')]);
        }
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

        if ($removedTenantIds !== []) {
            $terms = Dutiable::query()
                ->where('duty_id', $duty->id)
                ->where('dutiable_type', MorphMap::alias(User::class))
                ->whereIn('tenant_id', $removedTenantIds)
                ->whereNull('via_dutiable_id')
                ->notEnded();
            $members = User::whereKey($terms->pluck('dutiable_id')->unique()->all())->get();
            $this->assignments->auditMemberships(collect([$duty]), $members, fn () => $this->assignments->endTerms($terms));
        }

        $assignableTenants->detach($removedTenantIds);
        $assignableTenants->syncWithoutDetaching($this->assignments->buildAssignableTenantsSync($rows));

        $this->assignments->syncTenantRepresentatives($duty, $rows, $actor);

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
