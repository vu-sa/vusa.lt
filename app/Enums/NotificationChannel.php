<?php

namespace App\Enums;

/**
 * Available notification delivery channels.
 *
 * @typescript
 */
enum NotificationChannel: string
{
    case InApp = 'in_app';       // Database + Broadcast (real-time in-app)
    case Push = 'push';          // Web Push notifications
    case EmailDigest = 'email_digest'; // Batched email digest

    /**
     * Get the translation key for this channel.
     */
    public function labelKey(): string
    {
        return 'notifications.channels.'.$this->value;
    }

    /**
     * Get the description translation key for this channel.
     */
    public function descriptionKey(): string
    {
        return 'notifications.channels.'.$this->value.'_description';
    }

    /**
     * Check if this channel is enabled by default.
     */
    public function enabledByDefault(): bool
    {
        return match ($this) {
            self::InApp => true,
            self::Push => true,
            self::EmailDigest => true,
        };
    }

    /**
     * Get all channels as options for settings.
     *
     * @return array<string, array{value: string, enabledByDefault: bool}>
     */
    public static function toOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = [
                'value' => $case->value,
                'enabledByDefault' => $case->enabledByDefault(),
            ];
        }

        return $options;
    }
}
