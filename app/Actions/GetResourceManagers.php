<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetResourceManagers
{
    /**
     * @return Collection<int, User>
     */
    public static function execute(Resource $resource): Collection
    {
        $resourceManagers = Duty::whereHas('institution.tenant', function (Builder $query) use ($resource) {
            $query->where('id', $resource->tenant_id);
        })->whereHas('roles.permissions', function (Builder $query) {
            $query->where('name', config('permission.resource_managership_indicating_permission'));
        })->with('current_users')->get()->pluck('current_users')->flatten()->unique('id')->values();

        /** @var Collection<int, User> $result */
        $result = $resourceManagers;

        return $result;
    }
}
