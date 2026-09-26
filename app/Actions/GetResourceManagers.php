<?php

namespace App\Actions;

use App\Enums\PermissionScopeEnum;
use App\Models\Duty;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GetResourceManagers
{
    /**
     * The managership permission and its global variant. Permission rows are matched by name, so
     * Spatie's wildcard (`*` implies `padalinys`) never applies: without the `*` name a central
     * office role holding only `resources.update.*` was never found, even for its own resources.
     *
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        $permission = config('permission.resource_managership_indicating_permission');

        return [$permission, Str::beforeLast($permission, '.').'.'.PermissionScopeEnum::ALL->label()];
    }

    /**
     * @return Collection<int, User>
     */
    public static function execute(Resource $resource): Collection
    {
        $resourceManagers = Duty::whereHas('institution.tenant', function (Builder $query) use ($resource): void {
            $query->where('id', $resource->tenant_id);
        })->whereHas('roles.permissions', function (Builder $query): void {
            $query->whereIn('name', self::permissionNames());
        })->with('current_users')->get()->pluck('current_users')->flatten()->unique('id')->values();

        /** @var Collection<int, User> $result */
        $result = $resourceManagers;

        return $result;
    }
}
