<?php

namespace App\Listeners;

use App\Actions\ForgetUserAccess;
use App\Events\DutiableChanged;
use App\Models\User;
use App\Support\MorphMap;

class HandleDutiableChange
{
    public function handle(DutiableChanged $event): void
    {
        // Other morph types may share an id with a user without carrying their access.
        if ($event->dutiableType !== MorphMap::alias(User::class)) {
            return;
        }

        ForgetUserAccess::execute([$event->modelId]);
    }
}
