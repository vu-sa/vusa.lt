<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Builder;

class GetResourceManagers
{
    public static function execute(Resource $resource)
    {
        $resourceManagers = Duty::whereHas('institution.padalinys', function (Builder $query) use ($resource) {
            $query->where('id', $resource->padalinys_id);
        })->whereHas('roles.permissions', function (Builder $query) {
            $query->where('name', config('permission.resource_managership_indicating_permission'));
        })->with('users')->get()->pluck('users')->flatten()->unique('id')->values();

        return $resourceManagers;
    }
}
