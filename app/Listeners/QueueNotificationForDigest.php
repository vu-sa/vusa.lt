<?php

namespace App\Listeners;

use App\Enums\NotificationChannel;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Events\NotificationSending;

/**
 * Listener to queue notifications for email digest.
 *
 * This listener intercepts notifications before they are sent and
 * queues them for digest if the user has email digest enabled for
 * the notification category.
 */
class QueueNotificationForDigest
{
    /**
     * When true, notifications are not queued for digest.
     * Used during bulk operations like task repopulation.
     */
    public static bool $skipDigest = false;

    /**
     * Handle the event.
     */
    public function handle(NotificationSending $event): void
    {
        // Skip digest queuing during bulk operations
        if (self::$skipDigest) {
            return;
        }

        $notification = $event->notification;
        $notifiable = $event->notifiable;

        // Only process BaseNotification instances
        if (! $notification instanceof BaseNotification) {
            return;
        }

        // Only process for User notifiables
        if (! $notifiable instanceof User) {
            return;
        }

        // Check if this notification supports digest
        if (! $notification->supportsEmailDigest()) {
            return;
        }

        // Check if user has email digest enabled for this category
        if (! method_exists($notifiable, 'shouldReceiveNotification')) {
            return;
        }

        if (! $notifiable->shouldReceiveNotification($notification->category(), NotificationChannel::EmailDigest)) {
            return;
        }

        // Check if user is globally muted
        if ($notifiable->isNotificationMuted($notification)) {
            return;
        }

        // Queue for digest
        NotificationDigestQueue::create([
            'user_id' => $notifiable->id,
            'notification_class' => get_class($notification),
            'category' => $notification->category()->value,
            'data' => $notification->toDigestItem($notifiable),
        ]);
    }
}
