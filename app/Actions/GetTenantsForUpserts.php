<?php

namespace App\Actions;

use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Support\Facades\Auth;

/**
 * This class is always used in the context, when there's need for the input
 * of a tenant.
 */
class GetTenantsForUpserts
{
    public static function execute(string $permission, Authorizer $authorizer): \Illuminate\Support\Collection
    {
        $authorizer->forUser(Auth::user())->checkAllRoleables($permission);

        if ($authorizer->isAllScope) {
            return Tenant::query()->orderBy('shortname_vu')->get(['id', 'shortname', 'type'])->map(
                function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'shortname' => __($tenant->shortname),
                        'type' => $tenant->type,
                    ];
                }
            );
        }

        $duties = $authorizer->getPermissableDuties();

        $tenants = $duties->load('institution.tenant')->pluck('institution.tenant');

        return $tenants->map(
            function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'shortname' => __($tenant->shortname),
                    'type' => $tenant->type,
                ];
            }
        );
    }
}
