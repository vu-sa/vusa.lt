<?php

namespace App\Notifications;

use App\Enums\NotificationType;

/**
 * Welcome notification sent to users after completing their first tutorial.
 *
 * This provides a warm greeting to new users who have just started exploring
 * the platform. Sent only via database and broadcast channels (no email).
 */
class WelcomeNotification extends BaseNotification
{
    public function type(): NotificationType
    {
        return NotificationType::Welcome;
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
