<?php

namespace App\Actions;

use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Collection;

class GetTenantsForUpserts
{
    /**
     * Inertia callers compare the tenant type against strings.
     */
    public static function execute(User $user, string $permission, ModelAuthorizer $authorizer): Collection
    {
        return $authorizer->tenants($user, $permission)
            ->sortBy('shortname_vu')
            ->values()
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'shortname' => __($tenant->shortname),
                'type' => $tenant->type?->value,
            ]);
    }
}
