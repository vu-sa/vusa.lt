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
            'digest_emails' => [], // Empty means use default (duty email if exists, else user email)
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
            // Get values with array_key_exists check for optional fields
            $objectModelClass = $object['modelClass'];
            $objectId = array_key_exists('id', $object) ? $object['id'] : null;

            if (
                $thread['model_class'] === $objectModelClass &&
                $thread['model_id'] === $objectId
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

    /**
     * Get all available email addresses the user can use for digests.
     *
     * Returns the user's personal email and all current duty emails.
     *
     * @return array<array{email: string, label: string, type: string}>
     */
    public function getAvailableDigestEmails(): array
    {
        $emails = [];

        // User's personal email
        $emails[] = [
            'email' => $this->email,
            'label' => $this->email.' (asmeninis)',
            'type' => 'user',
        ];

        // Current duty emails
        /** @var \App\Models\Duty $duty */
        foreach ($this->current_duties()->get() as $duty) {
            if (! empty($duty->email)) {
                $emails[] = [
                    'email' => $duty->email,
                    'label' => $duty->email.' ('.$duty->name.')',
                    'type' => 'duty',
                ];
            }
        }

        return $emails;
    }

    /**
     * Get the email addresses to send notification digests to.
     *
     * Applies lazy cleanup: removes any emails that are no longer available
     * (e.g., from duties that have ended).
     *
     * @return array<string>
     */
    public function getDigestEmails(): array
    {
        $preferences = $this->notification_preferences;
        $configuredEmails = $preferences['digest_emails'] ?? [];
        $availableEmails = collect($this->getAvailableDigestEmails())->pluck('email')->toArray();

        // If no emails configured, use default behavior:
        // - First @vusa.lt duty email if exists
        // - Otherwise, user's personal email
        if (empty($configuredEmails)) {
            foreach ($this->current_duties()->get() as $duty) {
                if (! empty($duty->email) && str_ends_with($duty->email, 'vusa.lt')) {
                    return [$duty->email];
                }
            }

            return [$this->email];
        }

        // Lazy cleanup: filter to only currently available emails
        $validEmails = array_values(array_intersect($configuredEmails, $availableEmails));

        // If all configured emails became invalid, fall back to default
        if (empty($validEmails)) {
            return [$this->email];
        }

        return $validEmails;
    }

    /**
     * Set the email addresses to send notification digests to.
     *
     * @param  array<string>  $emails
     */
    public function setDigestEmails(array $emails): void
    {
        $availableEmails = collect($this->getAvailableDigestEmails())->pluck('email')->toArray();

        // Only store valid emails
        $validEmails = array_values(array_intersect($emails, $availableEmails));

        $preferences = $this->notification_preferences;
        $preferences['digest_emails'] = $validEmails;

        $this->update(['notification_preferences' => $preferences]);
    }
}
