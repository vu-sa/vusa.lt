<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;

/**
 * Welcome notification sent to users after completing their first tutorial.
 *
 * This provides a warm greeting to new users who have just started exploring
 * the platform. Sent only via database and broadcast channels (no email).
 */
class WelcomeNotification extends BaseNotification
{
    public function category(): NotificationCategory
    {
        return NotificationCategory::System;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.welcome_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.welcome_body', [
            'name' => $notifiable->name,
        ]);
    }

    public function url(): string
    {
        return route('dashboard');
    }

    public function icon(): string
    {
        return '🎉';
    }

    public function actions(): array
    {
        return [];
    }

    /**
     * Welcome notifications should not be digested or emailed.
     */
    public function supportsEmailDigest(): bool
    {
        return false;
    }
}
