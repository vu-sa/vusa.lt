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
     * Discard the job instead of failing when the dutiable can no longer be
     * restored — it is dispatched on `deleted`, so by the time the job runs the
     * row is gone and there is nothing left to invalidate.
     */
    public bool $deleteWhenMissingModels = true;

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
        Cache::forget('create-permissions-'.$userId);
        Permission::resetCache($userId);
    }
}
