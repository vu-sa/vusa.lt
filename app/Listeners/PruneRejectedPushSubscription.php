<?php

namespace App\Listeners;

use NotificationChannels\WebPush\Events\NotificationFailed;

/**
 * The push service rejects a subscription made under another VAPID key (production keys vs local,
 * or rotated keys) forever. The channel itself only prunes expired ones (404/410), so these piled
 * up as devices that never receive anything.
 */
class PruneRejectedPushSubscription
{
    public function handle(NotificationFailed $event): void
    {
        $response = $event->report->getResponse();

        if ($response === null) {
            return;
        }

        $status = $response->getStatusCode();
        $keyMismatch = $status === 401 || $status === 403
            || ($status === 400 && str_contains((string) $response->getBody(), 'VapidPkHashMismatch'));

        if ($keyMismatch) {
            $event->subscription->delete();
        }
    }
}
