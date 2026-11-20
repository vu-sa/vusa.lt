<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class DutyAccessService
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    public function allowsRead(User $user, Duty $duty): bool
    {
        $scope = $this->authorizer->scope($user, 'duties.read.padalinys');

        if ($scope->isAllScope || $this->authorizer->allows($user, 'duties.read.*')) {
            return true;
        }

        $tenantIds = $scope->tenantIds();
        if ($tenantIds->isNotEmpty()) {
            // `tenants` (the home tenant) stays loaded: the record payload has always carried it.
            $duty->loadMissing('tenants', 'assignableTenants');
            $dutyTenantIds = $duty->tenants->pluck('id')->merge($duty->assignableTenants->pluck('id'));

            if ($tenantIds->intersect($dutyTenantIds)->isNotEmpty()) {
                return true;
            }
        }

        return $duty->users()->whereKey($user->getKey())->exists();
    }

    /**
     * @param  Builder<Duty>  $query
     * @return Builder<Duty>
     */
    public function visibleDuties(Builder $query, User $user, bool $includeExternal = true): Builder
    {
        if ($this->authorizer->allows($user, 'duties.read.*') || $user->isSuperAdmin()) {
            return $query;
        }

        $tenantIds = $this->authorizer->scope($user, 'duties.read.padalinys')->tenantIds()->all();

        return $query->where(function (Builder $query) use ($tenantIds, $includeExternal, $user): void {
            $query->whereHas('institution.tenant', fn (Builder $tenantQuery) => $tenantQuery->whereIn('id', $tenantIds));

            if ($includeExternal) {
                $query->orWhereHas('assignableTenants', fn (Builder $tenantQuery) => $tenantQuery->whereIn('tenants.id', $tenantIds));
            }

            $query->orWhereHas('users', fn (Builder $holders) => $holders->whereKey($user->getKey()));
        });
    }

    /** The scoped key's filter: the tenant clause the key service built, or a duty the user holds or held. */
    public function searchFilter(User $user, ?string $tenantFilter): ?string
    {
        $held = $user->duties()->pluck('duties.id')->unique()->map(fn (string $id): string => "`{$id}`");
        $clauses = array_values(array_filter([$tenantFilter, $held->isEmpty() ? null : 'id:=['.$held->implode(',').']']));

        return match (count($clauses)) {
            0 => null,
            1 => $clauses[0],
            default => '('.implode(' || ', $clauses).')',
        };
    }
}
