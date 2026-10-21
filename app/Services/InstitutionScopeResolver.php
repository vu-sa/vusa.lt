<?php

namespace App\Services;

use App\Enums\InstitutionScope;
use App\Models\Institution;
use App\Models\InstitutionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves which governance world an institution belongs to.
 *
 * Scope is declared on a InstitutionType as `extra_attributes['governance_scope']` and inherited down
 * `types.parent_id`: the nearest ancestor (self first) carrying a value wins. The whole tree is
 * loaded in a single query and cached — `InstitutionType::getParentsAndSelf()` walks `recursiveParent` one
 * query per level, which would N+1 across any meeting or institution listing.
 *
 * Registered as a singleton so the map is built at most once per request.
 */
class InstitutionScopeResolver
{
    public const CACHE_KEY = 'institution_scopes';

    protected const CACHE_TTL = 3600;

    /**
     * A type that declares nothing, under a tree that declares nothing, is treated as a VU body —
     * that is what every institution in this app was before scopes existed.
     */
    public const DEFAULT = InstitutionScope::University;

    /** @var array<int, string|null>|null */
    private ?array $map = null;

    public function forType(?int $typeId): ?InstitutionScope
    {
        if ($typeId === null) {
            return null;
        }

        $value = $this->map()[$typeId] ?? null;

        return $value === null ? null : InstitutionScope::tryFrom($value);
    }

    /**
     * The institution's scope.
     *
     * An external type wins over an internal one when an institution carries both: the safe
     * direction is to keep asking for the student perspective. That also makes the rule
     * expressible as a plain `whereIn` over type ids — see typeIdsResolvingExternal().
     */
    public function forInstitution(Institution $institution): InstitutionScope
    {
        $institution->loadMissing('types');

        $resolved = $institution->types
            ->map(fn ($type) => $this->forType($type->id))
            ->filter();

        return $resolved->first(fn (InstitutionScope $scope) => $scope->isExternal())
            ?? $resolved->first()
            ?? self::DEFAULT;
    }

    /**
     * InstitutionType ids whose resolved scope is external — the SQL-side counterpart of forInstitution().
     *
     * An institution with none of these (and at least one type) is internal; one with no types
     * at all falls back to DEFAULT, which is external.
     *
     * @return array<int, int>
     */
    public function typeIdsResolvingExternal(): array
    {
        return collect($this->map())
            ->filter(fn (?string $value) => $value !== null && InstitutionScope::from($value)->isExternal())
            ->keys()
            ->all();
    }

    /**
     * InstitutionType ids whose resolved scope is internal. An institution is internal when it
     * carries one of these and none of typeIdsResolvingExternal().
     *
     * @return array<int, int>
     */
    public function typeIdsResolvingInternal(): array
    {
        return collect($this->map())
            ->filter(fn (?string $value) => $value !== null && InstitutionScope::from($value)->isInternal())
            ->keys()
            ->all();
    }

    /**
     * InstitutionType ids that resolve to exactly this scope.
     *
     * @return array<int, int>
     */
    public function typeIdsResolving(InstitutionScope $scope): array
    {
        return collect($this->map())
            ->filter(fn (?string $value) => $value === $scope->value)
            ->keys()
            ->all();
    }

    /**
     * Keep the institutions whose forInstitution() scope is one of `$scopes`, in SQL. An
     * institution carrying two different external scopes is kept when either is allowed.
     *
     * @param  Builder<Institution>  $institutions
     * @param  list<InstitutionScope>  $scopes
     * @return Builder<Institution>
     */
    public function constrainToScopes(Builder $institutions, array $scopes): Builder
    {
        $external = $this->typeIdsResolvingExternal();
        $scoped = [...$external, ...$this->typeIdsResolvingInternal()];
        $allowedExternal = collect($scopes)
            ->filter(fn (InstitutionScope $scope) => $scope->isExternal())
            ->flatMap(fn (InstitutionScope $scope) => $this->typeIdsResolving($scope))
            ->all();
        $internalAllowed = collect($scopes)->contains(fn (InstitutionScope $scope) => $scope->isInternal());

        return $institutions->where(fn (Builder $query) => $query
            ->whereHas('types', fn (Builder $types) => $types->whereIn('institution_types.id', $allowedExternal))
            ->when($internalAllowed, fn (Builder $query) => $query->orWhere(fn (Builder $internal) => $internal
                ->whereHas('types', fn (Builder $types) => $types->whereIn('institution_types.id', $this->typeIdsResolvingInternal()))
                ->whereDoesntHave('types', fn (Builder $types) => $types->whereIn('institution_types.id', $external))))
            ->when(in_array(self::DEFAULT, $scopes, true), fn (Builder $query) => $query
                ->orWhereDoesntHave('types', fn (Builder $types) => $types->whereIn('institution_types.id', $scoped))));
    }

    /**
     * Drop both the in-process memo and the shared cache. Called whenever a InstitutionType changes.
     */
    public function flush(): void
    {
        $this->map = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<int, string|null>
     */
    private function map(): array
    {
        return $this->map ??= Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn () => self::buildMap()
        );
    }

    /**
     * @return array<int, string|null>
     */
    private static function buildMap(): array
    {
        /** @var Collection<int, array{parent_id: int|null, scope: string|null}> $nodes */
        $nodes = InstitutionType::query()

            ->withTrashed()
            ->get(['id', 'parent_id', 'extra_attributes'])
            ->mapWithKeys(fn (InstitutionType $type) => [$type->id => [
                'parent_id' => $type->parent_id,
                'scope' => self::ownScopeValue($type),
            ]]);

        $resolved = [];

        foreach ($nodes as $id => $node) {
            $resolved[$id] = self::climb($nodes->all(), $id);
        }

        return $resolved;
    }

    /**
     * @param  array<int, array{parent_id: int|null, scope: string|null}>  $nodes
     */
    private static function climb(array $nodes, int $id): ?string
    {
        $seen = [];

        while (isset($nodes[$id]) && ! isset($seen[$id])) {
            $seen[$id] = true;

            if ($nodes[$id]['scope'] !== null) {
                return $nodes[$id]['scope'];
            }

            $parent = $nodes[$id]['parent_id'];

            if ($parent === null) {
                return null;
            }

            $id = $parent;
        }

        return null;
    }

    private static function ownScopeValue(InstitutionType $type): ?string
    {
        $value = $type->extra_attributes['governance_scope'] ?? null;

        return is_string($value) && InstitutionScope::tryFrom($value) !== null ? $value : null;
    }
}
