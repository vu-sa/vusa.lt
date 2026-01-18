<?php

namespace App\Models\Traits;

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Notifications\BaseNotification;
use Illuminate\Support\Carbon;

/**
 * Trait for managing user notification preferences.
 *
 * Add to User model:
 * - Use this trait
 * - Add 'notification_preferences' to $casts as 'array'
 *
 * @property array|null $notification_preferences
 */
trait HasNotificationPreferences
{
    /**
     * Default notification preferences structure.
     *
     * Note: News and Calendar categories are disabled by default (opt-in).
     */
    protected function getDefaultNotificationPreferences(): array
    {
        $channels = [];

        // Categories that are disabled by default (opt-in)
        $disabledByDefault = [
            NotificationCategory::News,
            NotificationCategory::Calendar,
        ];

        foreach (NotificationCategory::cases() as $category) {
            $isEnabled = ! in_array($category, $disabledByDefault, true);

            $channels[$category->value] = [
                NotificationChannel::InApp->value => $isEnabled,
                NotificationChannel::Push->value => $isEnabled,
                NotificationChannel::EmailDigest->value => $isEnabled,
            ];
        }

        return [
            'channels' => $channels,
            'digest_frequency_hours' => 4, // Default: every 4 hours
            'muted_until' => null,
            'muted_threads' => [],
            'reminder_settings' => [
                'task_reminder_days' => [7, 3, 1],
                'meeting_reminder_hours' => [24, 1],
                'calendar_reminder_hours' => [24],
            ],
        ];
    }

    /**
     * Get notification preferences with defaults applied.
     */
    public function getNotificationPreferencesAttribute($value): array
    {
        $preferences = $value ? (is_string($value) ? json_decode($value, true) : $value) : [];

        return array_replace_recursive($this->getDefaultNotificationPreferences(), $preferences);
    }

    /**
     * Check if user should receive notification for a category on a channel.
     */
    public function shouldReceiveNotification(NotificationCategory $category, NotificationChannel $channel): bool
    {
        $preferences = $this->notification_preferences;

        return $preferences['channels'][$category->value][$channel->value] ?? true;
    }

    /**
     * Check if all notifications are globally muted.
     */
    public function isGloballyMuted(): bool
    {
        $mutedUntil = $this->notification_preferences['muted_until'] ?? null;

        if (! $mutedUntil) {
            return false;
        }

        return Carbon::parse($mutedUntil)->isFuture();
    }

    /**
     * Check if a specific notification is muted (global or thread-specific).
     */
    public function isNotificationMuted(BaseNotification $notification): bool
    {
        // Check global mute
        if ($this->isGloballyMuted()) {
            return true;
        }

        // Check thread-specific mute
        $object = $notification->object();
        if (! $object) {
            return false;
        }

        $mutedThreads = $this->notification_preferences['muted_threads'] ?? [];

        foreach ($mutedThreads as $thread) {
            if (
                $thread['model_class'] === ($object['modelClass'] ?? null) &&
                $thread['model_id'] === ($object['id'] ?? null)
            ) {
                // Check if mute has expired
                if (isset($thread['until']) && Carbon::parse($thread['until'])->isPast()) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Mute all notifications until a specific time.
     */
    public function muteNotificationsUntil(?Carbon $until): void
    {
        $preferences = $this->notification_preferences;
        $preferences['muted_until'] = $until?->toIso8601String();

        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Unmute all notifications.
     */
    public function unmuteNotifications(): void
    {
        $this->muteNotificationsUntil(null);
    }

    /**
     * Mute a specific thread (model).
     */
    public function muteThread(string $modelClass, string $modelId, ?Carbon $until = null): void
    {
        $preferences = $this->notification_preferences;
        $mutedThreads = $preferences['muted_threads'] ?? [];

        // Remove existing mute for this thread if any
        $mutedThreads = array_filter($mutedThreads, function ($thread) use ($modelClass, $modelId) {
            return ! ($thread['model_class'] === $modelClass && $thread['model_id'] === $modelId);
        });

        // Add new mute
        $mutedThreads[] = [
            'model_class' => $modelClass,
            'model_id' => $modelId,
            'until' => $until?->toIso8601String(),
        ];

        $preferences['muted_threads'] = array_values($mutedThreads);
        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Unmute a specific thread.
     */
    public function unmuteThread(string $modelClass, string $modelId): void
    {
        $preferences = $this->notification_preferences;
        $mutedThreads = $preferences['muted_threads'] ?? [];

        $mutedThreads = array_filter($mutedThreads, function ($thread) use ($modelClass, $modelId) {
            return ! ($thread['model_class'] === $modelClass && $thread['model_id'] === $modelId);
        });

        $preferences['muted_threads'] = array_values($mutedThreads);
        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Update channel preference for a category.
     */
    public function setNotificationPreference(
        NotificationCategory $category,
        NotificationChannel $channel,
        bool $enabled
    ): void {
        $preferences = $this->notification_preferences;
        $preferences['channels'][$category->value][$channel->value] = $enabled;

        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Get the user's digest frequency in hours.
     */
    public function getDigestFrequencyHours(): int
    {
        return $this->notification_preferences['digest_frequency_hours'] ?? 4;
    }

    /**
     * Set the digest frequency in hours (1, 4, 12, or 24).
     */
    public function setDigestFrequencyHours(int $hours): void
    {
        $allowedValues = [1, 4, 12, 24];
        if (! in_array($hours, $allowedValues)) {
            $hours = 4; // Default to 4 if invalid
        }

        $preferences = $this->notification_preferences;
        $preferences['digest_frequency_hours'] = $hours;

        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Get custom task reminder days or default.
     *
     * @return array<int>
     */
    public function getTaskReminderDays(): array
    {
        return $this->notification_preferences['reminder_settings']['task_reminder_days'] ?? [7, 3, 1];
    }

    /**
     * Set custom task reminder days.
     *
     * @param  array<int>  $days
     */
    public function setTaskReminderDays(array $days): void
    {
        $preferences = $this->notification_preferences;
        $preferences['reminder_settings']['task_reminder_days'] = array_values(array_unique(array_filter($days, fn ($d) => $d > 0)));

        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Get custom meeting reminder hours or default.
     *
     * @return array<int>
     */
    public function getMeetingReminderHours(): array
    {
        return $this->notification_preferences['reminder_settings']['meeting_reminder_hours'] ?? [24, 1];
    }

    /**
     * Set custom meeting reminder hours.
     *
     * @param  array<int>  $hours
     */
    public function setMeetingReminderHours(array $hours): void
    {
        $preferences = $this->notification_preferences;
        $preferences['reminder_settings']['meeting_reminder_hours'] = array_values(array_unique(array_filter($hours, fn ($h) => $h > 0)));

        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Get custom calendar reminder hours or default.
     *
     * @return array<int>
     */
    public function getCalendarReminderHours(): array
    {
        return $this->notification_preferences['reminder_settings']['calendar_reminder_hours'] ?? [24];
    }

    /**
     * Set custom calendar reminder hours.
     *
     * @param  array<int>  $hours
     */
    public function setCalendarReminderHours(array $hours): void
    {
        $preferences = $this->notification_preferences;
        $preferences['reminder_settings']['calendar_reminder_hours'] = array_values(array_unique(array_filter($hours, fn ($h) => $h > 0)));

        $this->update(['notification_preferences' => $preferences]);
    }
}
