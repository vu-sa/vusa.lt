<?php

namespace App\Services;

use App\Enums\ResponsibilityPurpose;
use App\Enums\ResponsibilityScope;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\ResponsibilityGrant;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
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
        if (! isset($this->memo[$key])) {
            $this->loadForInstitutions($purpose, new Collection([$institution]));
        }

        return $this->memo[$key];
    }

    /** @param Collection<int, Institution> $institutions */
    public function loadForInstitutions(ResponsibilityPurpose $purpose, Collection $institutions): void
    {
        $pending = $institutions->reject(fn (Institution $institution): bool => isset($this->memo[$purpose->value.':'.$institution->id]));
        if ($pending->count() > 1) {
            $pending->loadMissing('types');
        }

        $matches = [];

        foreach ($pending as $institution) {
            $key = $purpose->value.':'.$institution->id;
            $matches[$key] = $this->winningAssignment($purpose, $institution);
        }

        $allIds = array_unique(array_merge(...array_column($matches, 'duty_ids')));
        $duties = $allIds === [] ? new Collection : Duty::query()->whereKey($allIds)->orderBy('order')->get();

        foreach ($matches as $key => $match) {
            $ids = array_flip($match['duty_ids']);
            $this->memo[$key] = [
                'scope' => $match['scope'],
                'duties' => $duties->filter(fn (Duty $duty): bool => isset($ids[$duty->id]))->values(),
            ];
        }
    }

    /** @return array{scope: ResponsibilityScope|null, duty_ids: list<string>} */
    private function winningAssignment(ResponsibilityPurpose $purpose, Institution $institution): array
    {
        if ($purpose->appliesTo($institution)) {
            foreach ($this->levels($institution) as [$scope, $ids]) {
                $dutyIds = $this->dutyIdsAt($purpose, $scope, $ids);

                if ($dutyIds !== []) {
                    return ['scope' => $scope, 'duty_ids' => $dutyIds];
                }
            }
        }

        return ['scope' => null, 'duty_ids' => []];
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
            ? $user->current_or_upcoming_duties()->pluck('duties.id')->map(strval(...))->all()
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
        if (isset($this->assignments[$purpose->value])) {
            return $this->assignments[$purpose->value];
        }

        $grants = ResponsibilityGrant::query()
            ->whereHas('role', fn (Builder $role) => $role->where('purpose', $purpose))
            ->get();
        $dutyIds = $this->dutyIdsByGrant($grants);
        $ownTenantDutyIds = $grants->filter(fn (ResponsibilityGrant $grant): bool => $grant->coversOwnTenant())
            ->flatMap(fn (ResponsibilityGrant $grant): array => $dutyIds[$grant->id])
            ->unique()->values()->all();
        $tenantIds = $this->tenantIdsByDuty($ownTenantDutyIds);

        return $this->assignments[$purpose->value] = $grants->toBase()
            ->flatMap(function (ResponsibilityGrant $grant) use ($dutyIds, $tenantIds): array {
                $ids = $dutyIds[$grant->id];

                if (! $grant->coversOwnTenant()) {
                    return [['scope_type' => $grant->scope_type, 'scope_id' => $grant->scope_id, 'duty_ids' => $ids]];
                }

                return collect($ids)->filter(fn (string $id): bool => isset($tenantIds[$id]))
                    ->groupBy(fn (string $id): string => (string) $tenantIds[$id])
                    ->map(fn (SupportCollection $ids, int|string $tenantId): array => [
                        'scope_type' => ResponsibilityScope::Tenant->value,
                        'scope_id' => (string) $tenantId,
                        'duty_ids' => array_values($ids->all()),
                    ])
                    ->values()->all();
            })
            ->values();
    }

    /**
     * @param  Collection<int, ResponsibilityGrant>  $grants
     * @return array<string, list<string>>
     */
    private function dutyIdsByGrant(Collection $grants): array
    {
        $dutyType = MorphMap::alias(DutyType::class);
        $institution = MorphMap::alias(Institution::class);
        $grantees = $grants->groupBy('grantee_type');

        $typeIds = $grantees->get($dutyType, collect())->pluck('grantee_id')->unique();
        $parents = $typeIds->isEmpty() ? [] : DutyType::liveParentMap();
        $descendants = $typeIds->mapWithKeys(fn (string $id): array => [$id => DutyType::withDescendantIds([$id], $parents)]);
        $expandedTypeIds = $descendants->flatten()->unique();
        $institutionIds = $grantees->get($institution, collect())->pluck('grantee_id')->unique();

        $byType = collect();
        $byInstitution = collect();

        if ($expandedTypeIds->isNotEmpty() || $institutionIds->isNotEmpty()) {
            // A grant can win scope even when all its duties are trashed.
            $viaType = DB::table('duty_duty_type')
                ->whereIn('duty_type_id', $expandedTypeIds)
                ->select('duty_type_id as grantee_id', 'duty_id')
                ->selectRaw('? as grantee_type', [$dutyType]);
            $viaInstitution = DB::table('duties')
                ->whereIn('institution_id', $institutionIds)
                ->select('institution_id as grantee_id', 'id as duty_id')
                ->selectRaw('? as grantee_type', [$institution]);
            $rows = $viaType->unionAll($viaInstitution)->get()->groupBy(['grantee_type', 'grantee_id']);
            $byType = $rows->get($dutyType, collect());
            $byInstitution = $rows->get($institution, collect());
        }

        $direct = MorphMap::alias(Duty::class);

        return $grants->mapWithKeys(function (ResponsibilityGrant $grant) use ($direct, $dutyType, $institution, $descendants, $byType, $byInstitution): array {
            $ids = match ($grant->grantee_type) {
                $direct => [$grant->grantee_id],
                $dutyType => collect($descendants->get($grant->grantee_id, []))
                    ->flatMap(fn (int $id) => $byType->get($id, collect())->pluck('duty_id'))
                    ->map(strval(...))->unique()->values()->all(),
                $institution => $byInstitution->get($grant->grantee_id, collect())
                    ->pluck('duty_id')->map(strval(...))->values()->all(),
                default => [],
            };

            return [$grant->id => $ids];
        })->all();
    }

    /**
     * @param  list<string>  $dutyIds
     * @return SupportCollection<string, int>
     */
    private function tenantIdsByDuty(array $dutyIds): SupportCollection
    {
        if ($dutyIds === []) {
            return collect();
        }

        return DB::table('duties')
            ->join('institutions', 'institutions.id', '=', 'duties.institution_id')
            ->whereIn('duties.id', $dutyIds)
            ->whereNotNull('institutions.tenant_id')
            ->pluck('institutions.tenant_id', 'duties.id');
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
