<?php

namespace App\Listeners;

use App\Events\RoleTypeDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class HandleRoleTypeDeleted
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RoleTypeDeleted $event): void
    {
        $role = $event->roleType->role;

        $type = $event->roleType->type;

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
