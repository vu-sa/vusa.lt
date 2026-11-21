<?php

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Collection;

class GetTenantsWithDutiesForForm
{
    /**
     * The frontend intersects this tree with the tenants from GetPermissableTenants.
     *
     * @return Collection<int, Tenant>
     */
    public static function execute(User $actor, ModelAuthorizer $authorizer, string $permission = 'users.create.*'): Collection
    {
        if (! $authorizer->allows($actor, $permission)) {
            return Tenant::orderBy('shortname')
                ->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
                ->whereIn('id', $actor->current_or_upcoming_tenants()->pluck('tenants.id'))
                ->get();
        }

        return Tenant::orderBy('shortname')
            ->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
            ->get();
    }
}
