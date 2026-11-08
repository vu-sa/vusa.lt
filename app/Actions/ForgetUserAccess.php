<?php

namespace App\Actions;

use App\Facades\Permission;
use App\Services\InstitutionAccessService;
use App\Services\Permissions\PermissionMapBuilder;
use App\Services\Typesense\TypesenseScopedKeyService;
use App\Support\AuthorityCacheExpiry;
use Illuminate\Support\Facades\DB;

/**
 * Drops every cache derived from a user's access, after their roles or permissions change.
 */
class ForgetUserAccess
{
    /**
     * @param  iterable<int|string>  $userIds
     */
    public static function execute(iterable $userIds): void
    {
        $userIds = collect($userIds)->map(strval(...))->filter()->unique()->values();

        // Waiting for commit prevents another request from re-warming caches from the old state.
        DB::afterCommit(function () use ($userIds): void {
            foreach ($userIds as $userId) {
                Permission::resetCache($userId);
                PermissionMapBuilder::forgetCachedMaps($userId);
                InstitutionAccessService::invalidateForUser($userId);
                TypesenseScopedKeyService::invalidateForUser($userId);
                AuthorityCacheExpiry::forget($userId);
            }
        });
    }
}
