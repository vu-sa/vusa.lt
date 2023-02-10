<?php

namespace App\Listeners;

use App\Events\DutiableChanged;
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
     * @param  App\Events\DutiableChanged  $event
     * @return void
     */
    public function handle(DutiableChanged $event)
    {
        Cache::forget('index-permissions-' . $event->dutiable->user->id);
    }
}
