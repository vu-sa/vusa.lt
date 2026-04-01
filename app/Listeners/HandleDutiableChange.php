<?php

namespace App\Listeners;

use App\Events\DutiableChanged;
use App\Facades\Permission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class HandleDutiableChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(DutiableChanged $event)
    {
        if (! $event->dutiable->user) {
            return;
        }

        $userId = $event->dutiable->user->id;

        Cache::forget('index-permissions-'.$userId);
        Permission::resetCache($userId);
    }
}
