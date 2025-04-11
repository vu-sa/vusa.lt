<?php

namespace App\Facades;

use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool check(string $permission, \App\Models\User $user = null)
 * @method static bool checkScope(string $resource, string $action, string $scope, \App\Models\User $user = null)
 * @method static bool isSuperAdmin(\App\Models\User $user = null)
 * @method static \Illuminate\Database\Eloquent\Collection getTenants(\App\Models\User $user = null)
 * @method static void resetCache(\App\Models\User|int|string $user)
 * 
 * @see \App\Services\PermissionService
 */
class Permission extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'permission.service';
    }
}