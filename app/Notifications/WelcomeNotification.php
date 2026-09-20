<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationUrgency;

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

    public function urgency(): NotificationUrgency
    {
        return NotificationUrgency::Onboarding;
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

    #[\Override]
    public function icon(): string
    {
        return '🎉';
    }
}
