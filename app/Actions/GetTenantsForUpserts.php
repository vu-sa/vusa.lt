<?php

namespace App\Actions;

use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * This class is always used in the context, when there's need for the input
 * of a tenant.
 */
class GetTenantsForUpserts
{
    /**
     * Note the `->value` on `type`: this array shape is handed straight to Inertia and compared
     * against plain strings by several callers, so the enum is unwrapped here rather than
     * leaking a TenantType into an array payload.
     */
    public static function execute(string $permission, Authorizer $authorizer): Collection
    {
        $authorizer->forUser(Auth::user())->checkAllRoleables($permission);

        if ($authorizer->isAllScope) {
            return Tenant::query()->orderBy('shortname_vu')->get(['id', 'shortname', 'type'])->map(
                fn ($tenant) => [
                    'id' => $tenant->id,
                    'shortname' => __($tenant->shortname),
                    'type' => $tenant->type?->value,
                ]
            );
        }

        $duties = $authorizer->getPermissableDuties();

        $tenants = $duties->load('institution.tenant')->pluck('institution.tenant');

        return $tenants->unique('id')->map(
            fn ($tenant) => [
                'id' => $tenant->id,
                'shortname' => __($tenant->shortname),
                'type' => $tenant->type?->value,
            ]
        );
    }
}
