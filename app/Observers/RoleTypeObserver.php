<?php

namespace App\Observers;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\RoleType;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Support\Facades\Cache;

class RoleTypeObserver
{
    public function saved(RoleType $roleType): void
    {
        $role = $roleType->role;

        $type = $roleType->type;

        $duties = [];

        // get duties

        if ($type->model_type === 'App\Models\Duty') {
            $duties = $type->duties;
        } else {
            return;
        }

        // borrowed from RoleController
        // sync duties

        $role->duties()->syncWithoutDetaching($duties);

        $role->usersThroughDuties->each(function ($user) {
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::registrationFormsCacheKey($user->id));
        });
    }

    /**
     * Handle the RoleType "deleted" event.
     */
    public function deleted(RoleType $roleType): void
    {
        $role = $roleType->role;

        $type = $roleType->type;

        $duties = [];

        // get duties

        if ($type->model_type === 'App\Models\Duty') {
            $duties = $type->duties;
        } else {
            return;
        }

        // borrowed from RoleController
        // sync duties

        $role->duties()->detach($duties);

        $role->usersThroughDuties->each(function ($user) {
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::registrationFormsCacheKey($user->id));
        });
    }
}
