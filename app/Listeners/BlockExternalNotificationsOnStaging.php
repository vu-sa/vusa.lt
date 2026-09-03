<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSending;

class BlockExternalNotificationsOnStaging
{
    public function handle(NotificationSending $event): ?bool
    {
        if (config('app.env') === 'staging' && $event->channel !== 'database') {
            return false;
        }

        return null;
    }
}
