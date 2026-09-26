<?php

namespace App\Models\Traits;

use App\Enums\EmailDelivery;
use App\Enums\NotificationType;
use App\Models\Duty;
use App\Services\NotificationRouter;
use Illuminate\Support\Carbon;

/**
 * Per-type notification preferences, stored as overrides only: a type without an entry uses its
 * NotificationType defaults, so changing a default reaches everyone who never touched it.
 *
 * @property array|null $notification_preferences
 */
trait HasNotificationPreferences
{
    public const array TASK_REMINDER_DAY_OPTIONS = [7, 3, 1];

    public const array MEETING_REMINDER_HOUR_OPTIONS = [24, 12, 1];

    public const array DIGEST_FREQUENCY_OPTIONS = [1, 4, 12, 24];

    /**
     * @return array{types: array<string, array{email?: string, push?: bool}>, digest_frequency_hours: int, emails: array<int, string>, muted_until: string|null, reminder_settings: array{task_reminder_days: array<int, int>, meeting_reminder_hours: array<int, int>}}
     */
    protected function getDefaultNotificationPreferences(): array
    {
        return [
            'types' => [],
            'digest_frequency_hours' => 4,
            'emails' => [],
            'muted_until' => null,
            'reminder_settings' => [
                'task_reminder_days' => [7, 3, 1],
                'meeting_reminder_hours' => [24, 1],
            ],
        ];
    }

    /**
     * Defaults fill missing keys only; lists are never merged, so a deselected reminder stays gone.
     */
    public function getNotificationPreferencesAttribute($value): array
    {
        $stored = $value ? (is_string($value) ? json_decode($value, true) : $value) : [];
        $defaults = $this->getDefaultNotificationPreferences();

        $preferences = array_replace($defaults, array_intersect_key($stored, $defaults));
        $preferences['reminder_settings'] = array_replace(
            $defaults['reminder_settings'],
            is_array($stored['reminder_settings'] ?? null) ? $stored['reminder_settings'] : [],
        );

        return $preferences;
    }

    public function emailDeliveryFor(NotificationType $type): EmailDelivery
    {
        if ($type->lockedEmail() !== null) {
            return $type->lockedEmail();
        }

        if (! $type->isConfigurable()) {
            return $type->defaultEmail();
        }

        $stored = $this->notification_preferences['types'][$type->value]['email'] ?? null;

        return EmailDelivery::tryFrom((string) $stored) ?? $type->defaultEmail();
    }

    public function wantsPushFor(NotificationType $type): bool
    {
        if (! $type->isConfigurable()) {
            return $type->defaultPush();
        }

        $stored = $this->notification_preferences['types'][$type->value]['push'] ?? null;

        return is_bool($stored) ? $stored : $type->defaultPush();
    }

    public function isGloballyMuted(): bool
    {
        $mutedUntil = $this->notification_preferences['muted_until'] ?? null;

        if (! $mutedUntil) {
            return false;
        }

        return Carbon::parse($mutedUntil)->isFuture();
    }

    public function muteNotificationsUntil(?Carbon $until): void
    {
        $preferences = $this->notification_preferences;
        $preferences['muted_until'] = $until?->toIso8601String();

        $this->update(['notification_preferences' => $preferences]);
    }

    public function unmuteNotifications(): void
    {
        $this->muteNotificationsUntil(null);
    }

    public function getDigestFrequencyHours(): int
    {
        return (int) $this->notification_preferences['digest_frequency_hours'];
    }

    /**
     * @return array<int, int>
     */
    public function getTaskReminderDays(): array
    {
        return $this->notification_preferences['reminder_settings']['task_reminder_days'];
    }

    /**
     * @return array<int, int>
     */
    public function getMeetingReminderHours(): array
    {
        return $this->notification_preferences['reminder_settings']['meeting_reminder_hours'];
    }

    /**
     * The personal address and every current duty address the user may send notifications to.
     *
     * @return array<int, array{email: string, label: string, type: string}>
     */
    public function getAvailableNotificationEmails(): array
    {
        $emails = [[
            'email' => $this->email,
            'label' => $this->email.' ('.__('notifications.preferences.personal_email').')',
            'type' => 'user',
        ]];

        /** @var Duty $duty */
        foreach ($this->current_duties()->get() as $duty) {
            if (! empty($duty->email) && ! in_array($duty->email, array_column($emails, 'email'), true)) {
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
     * Where immediate emails and the digest go: the chosen addresses that are still available,
     * else the default duty address ({@see NotificationRouter::preferredEmail()}).
     *
     * @return array<int, string>
     */
    public function notificationEmails(): array
    {
        $configured = $this->notification_preferences['emails'];

        if (! empty($configured)) {
            $available = array_column($this->getAvailableNotificationEmails(), 'email');
            $valid = array_values(array_intersect($configured, $available));

            if (! empty($valid)) {
                return $valid;
            }
        }

        return [app(NotificationRouter::class)->preferredEmail($this)];
    }
}
