<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Carbon\CarbonInterface;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Test notification for verifying push notification functionality.
 */
class TestPushNotification extends BaseNotification
{
    public function type(): NotificationType
    {
        return NotificationType::TestPush;
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
        return route('profile.notifications');
    }

    #[\Override]
    public function icon(): string
    {
        return '🔔';
    }

    /**
     * A test answers "does push reach this device?", so preferences, mute and quiet hours stay out of it.
     *
     * @return array<int, string>
     */
    #[\Override]
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    #[\Override]
    public function withDelay(object $notifiable, string $channel): ?CarbonInterface
    {
        return null;
    }
}
