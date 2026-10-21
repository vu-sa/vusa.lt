<?php

namespace App\Actions;

use App\Http\Middleware\HandleInertiaRequests;
use App\Observers\UserPermissionObserver;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Support\Facades\Cache;

/**
 * Drops every cache derived from a user's access, after their roles or permissions change.
 */
class ForgetUserAccess
{
    /**
     * @param  iterable<string>  $userIds
     */
    public static function execute(iterable $userIds): void
    {
        foreach (collect($userIds)->unique() as $userId) {
            app(UserPermissionObserver::class)->invalidateUserCache($userId);
            PermissionMapBuilder::forgetCachedMaps($userId);
            Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($userId));
        }
    }
}
