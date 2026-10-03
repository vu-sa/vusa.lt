<?php

namespace App\Services;

use App\Enums\Responsibility;
use App\Enums\ResponsibilityScope;
use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * The one answer to "who is responsible for this institution?".
 *
 * The most specific assignment wins: the institution itself, then its types (own types before
 * their ancestors), then its padalinys. Every duty assigned at the winning level counts. Scoped
 * per request, like ModelAuthorizer, so the memo never outlives an assignment change.
 */
class ResponsibilityResolver
{
    /** @var array<string, array{scope: ResponsibilityScope|null, duties: Collection<int, Duty>}> */
    private array $memo = [];

    /** @var array<int, int|null>|null */
    private ?array $typeParents = null;

    public function flush(): void
    {
        $this->memo = [];
        $this->typeParents = null;
    }

    /**
     * @return Collection<int, Duty>
     */
    public function dutiesFor(Responsibility $responsibility, Institution $institution): Collection
    {
        return $this->resolve($responsibility, $institution)['duties'];
    }

    /**
     * The level the winning assignment sits on, or null when nobody is responsible.
     */
    public function sourceFor(Responsibility $responsibility, Institution $institution): ?ResponsibilityScope
    {
        return $this->resolve($responsibility, $institution)['scope'];
    }

    /**
     * People currently holding a responsible duty.
     *
     * @return SupportCollection<int, User>
     */
    public function usersFor(Responsibility $responsibility, Institution $institution): SupportCollection
    {
        return $this->dutiesFor($responsibility, $institution)
            ->loadMissing('current_users')
            ->flatMap(fn (Duty $duty) => $duty->current_users)
            ->unique('id')
            ->values();
    }

    public function isResponsibleFor(User $user, Responsibility $responsibility, Institution $institution): bool
    {
        return $this->usersFor($responsibility, $institution)->contains('id', $user->id);
    }

    /**
     * Whether any of the user's current duties carries the responsibility, for any scope.
     */
    public function holdsAnywhere(User $user, Responsibility $responsibility): bool
    {
        return DutyResponsibility::query()
            ->where('responsibility', $responsibility)
            ->whereIn('duty_id', $user->current_duties()->pluck('duties.id'))
            ->exists();
    }

    /**
     * Institutions the user is actually responsible for: their assignments' reach, minus what a
     * more specific assignment gives to someone else.
     *
     * @return SupportCollection<int, string>
     */
    public function institutionIdsFor(User $user, Responsibility $responsibility): SupportCollection
    {
        $dutyIds = $user->current_duties()->pluck('duties.id')->map(fn ($id) => (string) $id);

        $assignments = DutyResponsibility::query()
            ->where('responsibility', $responsibility)
            ->whereIn('duty_id', $dutyIds)
            ->get();

        if ($assignments->isEmpty()) {
            return collect();
        }

        $byScope = $assignments->groupBy('scope_type');
        $idsOf = fn (ResponsibilityScope $scope) => $byScope->get($scope->value, collect())->pluck('scope_id')->all();

        $typeIds = $this->withDescendants(array_map(intval(...), $idsOf(ResponsibilityScope::InstitutionType)));

        $candidates = Institution::query()
            ->where(fn (Builder $query) => $query
                ->whereIn('id', $idsOf(ResponsibilityScope::Institution))
                ->orWhereIn('tenant_id', $idsOf(ResponsibilityScope::Tenant))
                ->orWhereHas('types', fn (Builder $types) => $types->whereIn('institution_types.id', $typeIds)))
            ->with('types')
            ->get();

        $candidateAssignments = DutyResponsibility::query()
            ->where('responsibility', $responsibility)
            ->whereHas('duty')
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $scope) => $scope
                    ->where('scope_type', ResponsibilityScope::Institution)
                    ->whereIn('scope_id', $candidates->pluck('id')->map(strval(...))))
                ->orWhere(fn (Builder $scope) => $scope
                    ->where('scope_type', ResponsibilityScope::Tenant)
                    ->whereIn('scope_id', $candidates->pluck('tenant_id')->filter()->map(strval(...))))
                ->orWhere(fn (Builder $scope) => $scope
                    ->where('scope_type', ResponsibilityScope::InstitutionType)
                    ->whereIn('scope_id', array_map(strval(...), array_keys($this->typeParents())))))
            ->get(['duty_id', 'scope_type', 'scope_id'])
            ->groupBy(fn (DutyResponsibility $assignment) => $assignment->scope_type.':'.$assignment->scope_id);

        return $candidates
            ->filter(function (Institution $institution) use ($responsibility, $candidateAssignments, $dutyIds): bool {
                if (! $responsibility->appliesTo($institution)) {
                    return false;
                }

                foreach ($this->levels($institution) as [$scope, $ids]) {
                    $winningDuties = collect($ids)
                        ->flatMap(fn (int|string $id) => $candidateAssignments
                            ->get($scope->value.':'.$id, collect())
                            ->pluck('duty_id'));

                    if ($winningDuties->isNotEmpty()) {
                        return $winningDuties->map(strval(...))->intersect($dutyIds)->isNotEmpty();
                    }
                }

                return false;
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();
    }

    /**
     * Representational padaliniai where nobody currently holds the responsibility for the whole
     * padalinys.
     *
     * @return Collection<int, Tenant>
     */
    public function gaps(Responsibility $responsibility): Collection
    {
        $covered = DutyResponsibility::query()
            ->where('responsibility', $responsibility)
            ->where('scope_type', ResponsibilityScope::Tenant)
            ->whereHas('duty', fn (Builder $duty) => $duty->whereHas('current_users'))
            ->pluck('scope_id');

        return Tenant::query()
            ->representational()
            ->whereNotIn('id', $covered->map(fn ($id) => (int) $id))
            ->orderBy('shortname')
            ->get();
    }

    /**
     * @return array{scope: ResponsibilityScope|null, duties: Collection<int, Duty>}
     */
    private function resolve(Responsibility $responsibility, Institution $institution): array
    {
        $key = $responsibility->value.':'.$institution->id;

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $none = ['scope' => null, 'duties' => new Collection];

        if (! $responsibility->appliesTo($institution)) {
            return $this->memo[$key] = $none;
        }

        foreach ($this->levels($institution) as [$scope, $ids]) {
            if ($ids === []) {
                continue;
            }

            $duties = Duty::query()
                ->whereHas('responsibilities', fn (Builder $query) => $query
                    ->where('responsibility', $responsibility)
                    ->where('scope_type', $scope)
                    ->whereIn('scope_id', array_map(strval(...), $ids)))
                ->orderBy('order')
                ->get();

            if ($duties->isNotEmpty()) {
                return $this->memo[$key] = ['scope' => $scope, 'duties' => $duties];
            }
        }

        return $this->memo[$key] = $none;
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
        $children = [];

        foreach ($this->typeParents() as $id => $parent) {
            if ($parent !== null) {
                $children[$parent][] = $id;
            }
        }

        $result = $typeIds;
        $frontier = $typeIds;

        while ($frontier !== []) {
            $frontier = array_values(array_unique(array_merge(...array_map(fn (int $id) => $children[$id] ?? [], $frontier))));
            $frontier = array_values(array_diff($frontier, $result));
            $result = [...$result, ...$frontier];
        }

        return $result;
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
