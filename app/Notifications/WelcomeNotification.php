<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;

/**
 * Welcome notification sent to users on their first login.
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
        return '👋';
    }

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_explore_dashboard'),
                'url' => route('dashboard'),
            ],
        ];
    }

    /**
     * Welcome notifications should not be digested.
     */
    public function supportsEmailDigest(): bool
    {
        return false;
    }
}
