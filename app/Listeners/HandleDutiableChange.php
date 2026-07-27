<?php

namespace App\Listeners;

use App\Events\DutiableChanged;
use App\Facades\Permission;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class HandleDutiableChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * This runs on deletion as well as saving: losing a duty changes a user's
     * permissions just as much as gaining one, so their cached maps must go either way.
     */
    public function handle(DutiableChanged $event): void
    {
        // Only User dutiables carry permissions. Contacts and other morph types have
        // none, and their id must not be used to look up a User that happens to share it.
        if ($event->dutiableType !== User::class) {
            return;
        }

        // resetCache() deliberately leaves these two maps alone, so forget them here.
        PermissionMapBuilder::forgetCachedMaps($event->modelId);
        Cache::forget(HandleInertiaRequests::registrationFormsCacheKey($event->modelId));

        Permission::resetCache($event->modelId);
    }
}
