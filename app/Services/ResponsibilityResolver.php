<?php

namespace App\Services;

use App\Enums\ResponsibilityPurpose;
use App\Enums\ResponsibilityScope;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\ResponsibilityGrant;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TypeTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * The one answer to "who is responsible for this institution?" for a purpose.
 *
 * The most specific grant wins: the institution itself, then its types (own types before their
 * ancestors), then its padalinys. Every duty granted at the winning level counts. Scoped per
 * request, like ModelAuthorizer, so the memo never outlives a grant change.
 */
class ResponsibilityResolver
{
    /** @var array<string, array{scope: ResponsibilityScope|null, duties: Collection<int, Duty>}> */
    private array $memo = [];

    /** @var array<string, SupportCollection<int, array{scope_type: string|null, scope_id: string|null, duty_ids: list<string>}>> */
    private array $assignments = [];

    /** @var array<int, int|null>|null */
    private ?array $typeParents = null;

    public function flush(): void
    {
        $this->memo = [];
        $this->assignments = [];
        $this->typeParents = null;
    }

    /**
     * @return Collection<int, Duty>
     */
    public function dutiesFor(ResponsibilityPurpose $purpose, Institution $institution): Collection
    {
        return $this->resolve($purpose, $institution)['duties'];
    }

    /**
     * The level the winning grant sits on, or null when nobody is responsible.
     */
    public function sourceFor(ResponsibilityPurpose $purpose, Institution $institution): ?ResponsibilityScope
    {
        return $this->resolve($purpose, $institution)['scope'];
    }

    /**
     * People currently holding a responsible duty.
     *
     * @return SupportCollection<int, User>
     */
    public function usersFor(ResponsibilityPurpose $purpose, Institution $institution): SupportCollection
    {
        return $this->dutiesFor($purpose, $institution)
            ->loadMissing('current_users')
            ->flatMap(fn (Duty $duty) => $duty->current_users)
            ->unique('id')
            ->values();
    }

    public function isResponsibleFor(User $user, ResponsibilityPurpose $purpose, Institution $institution): bool
    {
        return $this->usersFor($purpose, $institution)->contains('id', $user->id);
    }

    /**
     * Whether any of the user's current duties carries the purpose, for any scope.
     */
    public function holdsAnywhere(User $user, ResponsibilityPurpose $purpose): bool
    {
        return $this->assignmentsReaching($user, $purpose)->isNotEmpty();
    }

    /**
     * Padaliniai the user's duties are granted the purpose for as a whole. Access checks pass
     * `$includeScheduled`, so a term that has not started yet opens what its permissions already do.
     *
     * @return SupportCollection<int, int>
     */
    public function tenantIdsFor(User $user, ResponsibilityPurpose $purpose, bool $includeScheduled = false): SupportCollection
    {
        return $this->assignmentsReaching($user, $purpose, $includeScheduled)
            ->where('scope_type', ResponsibilityScope::Tenant->value)
            ->pluck('scope_id')
            ->map(fn (string $id): int => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Duties granted the purpose for the whole padalinys, in contact order.
     *
     * @return Collection<int, Duty>
     */
    public function dutiesForTenant(ResponsibilityPurpose $purpose, Tenant $tenant): Collection
    {
        return Duty::query()
            ->whereHas('institution')
            ->whereIn('id', $this->dutyIdsAt($purpose, ResponsibilityScope::Tenant, [$tenant->id]))
            ->orderBy('order')
            ->get();
    }

    /**
     * Institutions the user is actually responsible for: their grants' reach, minus what a more
     * specific grant gives to someone else.
     *
     * @return SupportCollection<int, string>
     */
    public function institutionIdsFor(User $user, ResponsibilityPurpose $purpose): SupportCollection
    {
        $own = $this->assignmentsReaching($user, $purpose);

        if ($own->isEmpty()) {
            return collect();
        }

        $dutyIds = $this->currentDutyIds($user);
        $idsOf = fn (ResponsibilityScope $scope) => $own->where('scope_type', $scope->value)->pluck('scope_id')->all();
        $typeIds = $this->withDescendants(array_map(intval(...), $idsOf(ResponsibilityScope::InstitutionType)));

        return Institution::query()
            ->where(fn (Builder $query) => $query
                ->whereIn('id', $idsOf(ResponsibilityScope::Institution))
                ->orWhereIn('tenant_id', $idsOf(ResponsibilityScope::Tenant))
                ->orWhereHas('types', fn (Builder $types) => $types->whereIn('institution_types.id', $typeIds)))
            ->with('types')
            ->get()
            ->filter(function (Institution $institution) use ($purpose, $dutyIds): bool {
                if (! $purpose->appliesTo($institution)) {
                    return false;
                }

                foreach ($this->levels($institution) as [$scope, $ids]) {
                    $winning = $this->dutyIdsAt($purpose, $scope, $ids);

                    if ($winning !== []) {
                        return array_intersect($winning, $dutyIds) !== [];
                    }
                }

                return false;
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();
    }

    /**
     * Representational padaliniai where nobody currently holds the purpose for the whole padalinys.
     *
     * @return Collection<int, Tenant>
     */
    public function gaps(ResponsibilityPurpose $purpose): Collection
    {
        $staffed = Duty::query()
            ->whereIn('id', $this->assignments($purpose)->pluck('duty_ids')->flatten()->all())
            ->whereHas('current_users')
            ->pluck('id')
            ->map(strval(...))
            ->all();

        $covered = $this->assignments($purpose)
            ->where('scope_type', ResponsibilityScope::Tenant->value)
            ->filter(fn (array $assignment): bool => array_intersect($assignment['duty_ids'], $staffed) !== [])
            ->pluck('scope_id')
            ->map(fn (string $id): int => (int) $id);

        return Tenant::query()
            ->representational()
            ->whereNotIn('id', $covered)
            ->orderBy('shortname')
            ->get();
    }

    /**
     * @return array{scope: ResponsibilityScope|null, duties: Collection<int, Duty>}
     */
    private function resolve(ResponsibilityPurpose $purpose, Institution $institution): array
    {
        $key = $purpose->value.':'.$institution->id;

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $none = ['scope' => null, 'duties' => new Collection];

        if (! $purpose->appliesTo($institution)) {
            return $this->memo[$key] = $none;
        }

        foreach ($this->levels($institution) as [$scope, $ids]) {
            $dutyIds = $this->dutyIdsAt($purpose, $scope, $ids);

            if ($dutyIds !== []) {
                return $this->memo[$key] = [
                    'scope' => $scope,
                    'duties' => Duty::query()->whereIn('id', $dutyIds)->orderBy('order')->get(),
                ];
            }
        }

        return $this->memo[$key] = $none;
    }

    /**
     * @param  list<int|string>  $scopeIds
     * @return list<string>
     */
    private function dutyIdsAt(ResponsibilityPurpose $purpose, ResponsibilityScope $scope, array $scopeIds): array
    {
        $scopeIds = array_map(strval(...), $scopeIds);

        return $this->assignments($purpose)
            ->where('scope_type', $scope->value)
            ->filter(fn (array $assignment): bool => in_array($assignment['scope_id'], $scopeIds, true))
            ->pluck('duty_ids')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return SupportCollection<int, array{scope_type: string|null, scope_id: string|null, duty_ids: list<string>}>
     */
    private function assignmentsReaching(User $user, ResponsibilityPurpose $purpose, bool $includeScheduled = false): SupportCollection
    {
        $dutyIds = $includeScheduled
            ? $user->authorization_duties()->pluck('duties.id')->map(strval(...))->all()
            : $this->currentDutyIds($user);

        return $this->assignments($purpose)
            ->filter(fn (array $assignment): bool => array_intersect($assignment['duty_ids'], $dutyIds) !== [])
            ->values();
    }

    /**
     * Each grant of the purpose's responsibility with the duties it reaches.
     *
     * @return SupportCollection<int, array{scope_type: string|null, scope_id: string|null, duty_ids: list<string>}>
     */
    private function assignments(ResponsibilityPurpose $purpose): SupportCollection
    {
        return $this->assignments[$purpose->value] ??= ResponsibilityGrant::query()
            ->whereHas('role', fn (Builder $role) => $role->where('purpose', $purpose))
            ->get()
            ->flatMap(function (ResponsibilityGrant $grant): array {
                $dutyIds = ResponsibilityGrant::dutyIdsQuery(ResponsibilityGrant::query()->whereKey($grant->id))
                    ->pluck('duty_id')
                    ->map(strval(...))
                    ->all();

                if (! $grant->coversOwnTenant()) {
                    return [['scope_type' => $grant->scope_type, 'scope_id' => $grant->scope_id, 'duty_ids' => $dutyIds]];
                }

                return $this->dutyIdsByTenant($dutyIds)
                    ->map(fn (array $ids, string $tenantId): array => ['scope_type' => ResponsibilityScope::Tenant->value, 'scope_id' => $tenantId, 'duty_ids' => $ids])
                    ->values()
                    ->all();
            })
            ->values();
    }

    /**
     * @param  list<string>  $dutyIds
     * @return SupportCollection<int|string, list<string>>
     */
    private function dutyIdsByTenant(array $dutyIds): SupportCollection
    {
        return DB::table('duties')
            ->join('institutions', 'institutions.id', '=', 'duties.institution_id')
            ->whereIn('duties.id', $dutyIds)
            ->whereNotNull('institutions.tenant_id')
            ->get(['duties.id', 'institutions.tenant_id'])
            ->groupBy(fn (object $row): string => (string) $row->tenant_id)
            ->map(fn (SupportCollection $rows): array => array_values($rows->pluck('id')->map(strval(...))->all()));
    }

    /**
     * @return list<string>
     */
    private function currentDutyIds(User $user): array
    {
        return $user->current_duties()->pluck('duties.id')->map(strval(...))->all();
    }

    /**
     * Scopes from most to least specific; each type ancestry step is its own level.
     *
     * @return iterable<array{0: ResponsibilityScope, 1: list<int|string>}>
     */
    private function levels(Institution $institution): iterable
    {
        yield [ResponsibilityScope::Institution, [(string) $institution->id]];

        $institution->loadMissing('types');
        $parents = $this->typeParents();
        $seen = [];
        $level = $institution->types->pluck('id')->map(fn ($id) => (int) $id)->all();

        while ($level !== []) {
            $level = array_values(array_diff($level, $seen));
            $seen = [...$seen, ...$level];

            yield [ResponsibilityScope::InstitutionType, $level];

            $level = array_values(array_unique(array_filter(array_map(fn (int $id) => $parents[$id] ?? null, $level))));
        }

        yield [ResponsibilityScope::Tenant, $institution->tenant_id === null ? [] : [$institution->tenant_id]];
    }

    /**
     * @param  list<int>  $typeIds
     * @return list<int>
     */
    private function withDescendants(array $typeIds): array
    {
        return TypeTree::withDescendants($this->typeParents(), $typeIds);
    }

    /**
     * @return array<int, int|null>
     */
    private function typeParents(): array
    {
        return $this->typeParents ??= InstitutionType::query()
            ->pluck('parent_id', 'id')
            ->mapWithKeys(fn ($parent, $id) => [(int) $id => $parent === null ? null : (int) $parent])
            ->all();
    }
}
