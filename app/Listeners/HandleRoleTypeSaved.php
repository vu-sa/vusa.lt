<?php

namespace App\Listeners;

use App\Events\RoleTypeSaved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class HandleRoleTypeSaved
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
    public function handle(RoleTypeSaved $event): void
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

        $role->duties()->syncWithoutDetaching($duties);

        $role->usersThroughDuties->each(function ($user) {
            Cache::forget('index-permissions-'.$user->id);
        });
    }
}
