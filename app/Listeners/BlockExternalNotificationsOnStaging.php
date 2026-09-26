<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSending;
use NotificationChannels\WebPush\WebPushChannel;

class BlockExternalNotificationsOnStaging
{
    public function handle(NotificationSending $event): ?bool
    {
        if (config('app.env') !== 'staging') {
            return null;
        }

        $allowed = match ($event->channel) {
            'database' => true,
            'broadcast' => config('app.staging_broadcasting_enabled') === true,
            WebPushChannel::class => config('app.staging_push_enabled') === true,
            default => false,
        };

        return $allowed ? null : false;
    }
}
