<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Builder;

class GetResourceManagers
{
    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    public static function execute(Resource $resource): \Illuminate\Support\Collection
    {
        $resourceManagers = Duty::whereHas('institution.tenant', function (Builder $query) use ($resource) {
            $query->where('id', $resource->tenant_id);
        })->whereHas('roles.permissions', function (Builder $query) {
            $query->where('name', config('permission.resource_managership_indicating_permission'));
        })->with('current_users')->get()->pluck('current_users')->flatten()->unique('id')->values();

        /** @var \Illuminate\Support\Collection<int, \App\Models\User> $result */
        $result = $resourceManagers;

        return $result;
    }
}
