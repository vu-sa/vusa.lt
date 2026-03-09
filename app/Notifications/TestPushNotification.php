<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;

/**
 * Test notification for verifying push notification functionality.
 */
class TestPushNotification extends BaseNotification
{
    public function category(): NotificationCategory
    {
        return NotificationCategory::System;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.test_notification_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.test_notification_body');
    }

    public function url(): string
    {
        return route('profile');
    }

    public function icon(): string
    {
        return '🔔';
    }

    /**
     * Test notifications should not be queued for digest.
     */
    public function supportsEmailDigest(): bool
    {
        return false;
    }
}
