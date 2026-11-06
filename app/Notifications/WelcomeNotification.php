<?php

namespace App\Notifications;

use App\Enums\NotificationType;

/** Welcome after the first tutorial; delivered in the app by default. */
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
