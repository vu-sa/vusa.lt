<?php

namespace App\Services\ResourceServices;

use App\Models\Doing;

class DoingService
{
    public static function handleDecision(Doing $doing, string $decision)
    {
        // decisions: approve, reject, submit, cancel

        $transitionableStates = $doing->state->transitionableStates();
    }
}
