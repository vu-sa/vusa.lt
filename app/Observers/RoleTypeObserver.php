<?php

namespace App\Observers;

use App\Models\RoleType;
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
            Cache::forget('index-permissions-'.$user->id);
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
            Cache::forget('index-permissions-'.$user->id);
        });
    }
}
