<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

class IdempotentDatabaseChannel extends DatabaseChannel
{
    /**
     * A retried SendQueuedNotifications job re-runs every channel, including one that
     * already committed on a prior attempt (e.g. broadcast/WebPush fails after this
     * channel succeeds). updateOrCreate keeps the retry a no-op instead of a
     * duplicate-key failure on the notification's fixed UUID.
     */
    public function send($notifiable, Notification $notification)
    {
        $payload = $this->buildPayload($notifiable, $notification);

        return $notifiable->routeNotificationFor('database', $notification)
            ->updateOrCreate(['id' => $payload['id']], $payload);
    }
}
