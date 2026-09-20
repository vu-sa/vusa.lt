<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The member list's base query — one definition for the Inertia page and its API twin.
 *
 * Tenant-scoped user administration reaches people with a duty history (or a role); the tenant
 * scope itself is applied by the caller through `applyTanstackFilters(... 'tenantRelation' =>
 * 'tenants')`. Dutyless, roleless accounts are never part of this list (.ai/rules/policies-http-requests.md).
 */
final class BuildUserIndexQuery
{
    /**
     * @return Builder<User>
     */
    public static function execute(): Builder
    {
        return User::query()
            ->where(fn (Builder $query) => $query
                ->whereHas('duties')
                ->orWhereHas('roles'))
            ->with([
                'duties:id,institution_id',
                'duties.institution:id,tenant_id',
                'duties.institution.tenant:id,shortname',
            ])->withCount('duties');
    }
}
