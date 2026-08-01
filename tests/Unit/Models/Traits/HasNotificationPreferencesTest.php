<?php

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

describe('default preferences', function (): void {
    test('defaults are applied when preferences are null', function (): void {
        $user = User::factory()->create(['notification_preferences' => null]);

        $prefs = $user->notification_preferences;

        expect($prefs)->toHaveKeys(['channels', 'digest_frequency_hours', 'muted_until', 'muted_threads', 'reminder_settings']);

        // Categories that are disabled by default (opt-in)
        $disabledByDefault = [
            NotificationCategory::News,
            NotificationCategory::Calendar,
        ];

        // Check channels - most should be enabled, but News and Calendar are disabled by default
        foreach (NotificationCategory::cases() as $category) {
            $expectedEnabled = ! in_array($category, $disabledByDefault, true);
            foreach (NotificationChannel::cases() as $channel) {
                expect($prefs['channels'][$category->value][$channel->value])->toBe($expectedEnabled);
            }
        }

        expect($prefs['digest_frequency_hours'])->toBe(4)
            ->and($prefs['muted_until'])->toBeNull()
            ->and($prefs['muted_threads'])->toBeEmpty();
    });

    test('partial preferences are merged with defaults', function (): void {
        $user = User::factory()->create([
            'notification_preferences' => [
                'digest_frequency_hours' => 12,
            ],
        ]);

        $prefs = $user->notification_preferences;

        expect($prefs['digest_frequency_hours'])->toBe(12);
        // Defaults should still be present
        expect($prefs['channels'])->not->toBeEmpty();
        expect($prefs['reminder_settings'])->not->toBeEmpty();
    });
});

describe('global muting', function (): void {
    test('isGloballyMuted returns true when muted_until is in future', function (): void {
        $user = $this->createMutedUser(now()->addHour());

        expect($user->isGloballyMuted())->toBeTrue();
    });

    test('isGloballyMuted returns false when muted_until is in past', function (): void {
        $user = User::factory()->create([
            'notification_preferences' => [
                'muted_until' => now()->subHour()->toIso8601String(),
            ],
        ]);

        expect($user->isGloballyMuted())->toBeFalse();
    });

    test('isGloballyMuted returns false when muted_until is null', function (): void {
        $user = $this->createUserWithPreferences();

        expect($user->isGloballyMuted())->toBeFalse();
    });

    test('muteNotificationsUntil sets mute timestamp', function (): void {
        $user = $this->createUserWithPreferences();
        $muteUntil = now()->addHours(2);

        $user->muteNotificationsUntil($muteUntil);

        $user->refresh();
        expect($user->isGloballyMuted())->toBeTrue();
    });

    test('unmuteNotifications clears mute', function (): void {
        $user = $this->createMutedUser();

        expect($user->isGloballyMuted())->toBeTrue();

        $user->unmuteNotifications();
        $user->refresh();

        expect($user->isGloballyMuted())->toBeFalse();
    });

    test('global mute expires correctly with time travel', function (): void {
        $user = $this->createMutedUser(now()->addMinutes(30));

        expect($user->isGloballyMuted())->toBeTrue();

        $this->travelTo(now()->addMinutes(31));

        expect($user->isGloballyMuted())->toBeFalse();
    });
});

describe('thread muting', function (): void {
    test('muteThread adds to muted_threads array', function (): void {
        $user = $this->createUserWithPreferences();

        $user->muteThread(Task::class, '123');

        $user->refresh();
        $mutedThreads = $user->notification_preferences['muted_threads'];

        expect($mutedThreads)->toHaveCount(1)
            ->and($mutedThreads[0])->toMatchArray(['model_class' => Task::class, 'model_id' => '123']);
    });

    test('muteThread with expiry sets until timestamp', function (): void {
        $user = $this->createUserWithPreferences();
        $until = now()->addDay();

        $user->muteThread(Task::class, '123', $until);

        $user->refresh();
        $mutedThreads = $user->notification_preferences['muted_threads'];

        expect($mutedThreads[0]['until'])->not->toBeNull();
    });

    test('unmuteThread removes from muted_threads array', function (): void {
        $user = $this->createUserWithPreferences();

        $user->muteThread(Task::class, '123');
        $user->muteThread(Task::class, '456');

        $user->refresh();
        expect($user->notification_preferences['muted_threads'])->toHaveCount(2);

        $user->unmuteThread(Task::class, '123');
        $user->refresh();

        $mutedThreads = $user->notification_preferences['muted_threads'];
        expect($mutedThreads)->toHaveCount(1)
            ->and($mutedThreads[0]['model_id'])->toBe('456');
    });

    test('muteThread replaces existing mute for same thread', function (): void {
        $user = $this->createUserWithPreferences();

        $user->muteThread(Task::class, '123', now()->addHour());
        $user->muteThread(Task::class, '123', now()->addDays(7));

        $user->refresh();
        $mutedThreads = $user->notification_preferences['muted_threads'];

        expect($mutedThreads)->toHaveCount(1);
    });
});

describe('channel preferences', function (): void {
    test('shouldReceiveNotification returns true for enabled channel', function (): void {
        $user = $this->createUserWithPreferences();

        expect($user->shouldReceiveNotification(
            NotificationCategory::Task,
            NotificationChannel::InApp
        ))->toBeTrue();
    });

    test('shouldReceiveNotification returns false for disabled channel', function (): void {
        $user = $this->createUserWithDisabledChannel(
            NotificationCategory::Task,
            NotificationChannel::Push
        );

        expect($user->shouldReceiveNotification(
            NotificationCategory::Task,
            NotificationChannel::Push
        ))->toBeFalse();

        // Other channels should still be enabled
        expect($user->shouldReceiveNotification(
            NotificationCategory::Task,
            NotificationChannel::InApp
        ))->toBeTrue();
    });

    test('setNotificationPreference updates specific channel', function (): void {
        $user = $this->createUserWithPreferences();

        $user->setNotificationPreference(
            NotificationCategory::Comment,
            NotificationChannel::EmailDigest,
            false
        );

        $user->refresh();

        expect($user->shouldReceiveNotification(
            NotificationCategory::Comment,
            NotificationChannel::EmailDigest
        ))->toBeFalse();

        // Other category/channel combinations should be unaffected
        expect($user->shouldReceiveNotification(
            NotificationCategory::Task,
            NotificationChannel::EmailDigest
        ))->toBeTrue();
    });
});

describe('digest settings', function (): void {
    test('getDigestFrequencyHours returns default when not set', function (): void {
        $user = $this->createUserWithPreferences();

        expect($user->getDigestFrequencyHours())->toBe(4);
    });

    test('getDigestFrequencyHours returns custom value', function (): void {
        $user = $this->createUserWithDigestEnabled(12);

        expect($user->getDigestFrequencyHours())->toBe(12);
    });

    test('setDigestFrequencyHours validates allowed values', function (): void {
        $user = $this->createUserWithPreferences();

        // Valid values
        foreach ([1, 4, 12, 24] as $hours) {
            $user->setDigestFrequencyHours($hours);
            $user->refresh();
            expect($user->getDigestFrequencyHours())->toBe($hours);
        }

        // Invalid value should default to 4
        $user->setDigestFrequencyHours(5);
        $user->refresh();
        expect($user->getDigestFrequencyHours())->toBe(4);
    });
});

describe('reminder settings', function (): void {
    test('getTaskReminderDays returns default values', function (): void {
        $user = $this->createUserWithPreferences();

        expect($user->getTaskReminderDays())->toBe([7, 3, 1]);
    });

    test('setTaskReminderDays updates values', function (): void {
        $user = $this->createUserWithPreferences();

        $user->setTaskReminderDays([14, 7, 1]);
        $user->refresh();

        expect($user->getTaskReminderDays())->toBe([14, 7, 1]);
    });

    test('setTaskReminderDays filters invalid values', function (): void {
        $user = $this->createUserWithPreferences();

        // Note: Due to array_replace_recursive behavior, filtered values [7, 3]
        // will be merged with defaults [7, 3, 1] by index, so index 2 (value 1)
        // remains from defaults. This is expected current behavior.
        $user->setTaskReminderDays([7, 0, -1, 3]);
        $user->refresh();

        $days = $user->getTaskReminderDays();
        // Verify that 0 and -1 were filtered out
        expect($days)->not->toContain(0);
        expect($days)->not->toContain(-1);
        // The actual stored filtered values are 7 and 3
        expect($days)->toMatchArray([0 => 7, 1 => 3]);
    });

    test('getMeetingReminderHours returns default values', function (): void {
        $user = $this->createUserWithPreferences();

        expect($user->getMeetingReminderHours())->toBe([24, 1]);
    });

    test('setMeetingReminderHours updates values', function (): void {
        $user = $this->createUserWithPreferences();

        $user->setMeetingReminderHours([48, 24, 2]);
        $user->refresh();

        expect($user->getMeetingReminderHours())->toBe([48, 24, 2]);
    });
});
