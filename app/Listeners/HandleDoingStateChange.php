<?php

namespace App\Listeners;

use App\Models\Doing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\ModelStates\Events\StateChanged;

class HandleDoingStateChange
{
    public function handle(StateChanged $event)
    {
        $doing = $event->model;

        dd($event);

        // check if doing is instance of Doing
        if (! $doing instanceof Doing) {
            return;
        }

        $initialState = $event->initialState;
        $finalState = $event->finalState;

        dd($initialState, $finalState, $doing, $event);
    }
}