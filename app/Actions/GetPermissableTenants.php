<?php

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Collection;

class GetPermissableTenants
{
    /** @return Collection<int, Tenant> */
    public static function execute(User $actor, ModelAuthorizer $authorizer, string $permission): Collection
    {
        if ($actor->isSuperAdmin()) {
            return Tenant::all();
        }

        if (! $authorizer->allows($actor, $permission)) {
            return new Collection;
        }

        return $authorizer->tenants($actor, $permission);
    }
}
