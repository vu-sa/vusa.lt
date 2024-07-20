<?php

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Facades\Auth;

class GetTenantsForUpserts
{
    // e.g. 'institutions.create.all'
    public static function execute(string $permission, ModelAuthorizer $authorizer): \Illuminate\Support\Collection
    {
        // ! must be already authorized for this action
        if (! $authorizer->forUser(Auth::user())->checkAllRoleables($permission)) {
            return User::query()->with('tenants:tenants.id,shortname')->find(Auth::user()->id)->tenants->unique()->map(
                function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'shortname' => __($tenant->shortname),
                    ];
                }
            );
        }

        return Tenant::query()->orderBy('shortname_vu')->get(['id', 'shortname'])->map(
            function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'shortname' => __($tenant->shortname),
                ];
            }
        );
    }
}
