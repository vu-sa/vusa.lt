<?php

use App\Enums\EmailDelivery;
use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

describe('default preferences', function (): void {
    test('defaults are applied when preferences are null', function (): void {
        $user = User::factory()->create(['notification_preferences' => null]);

        expect($user->notification_preferences)->toBe([
            'types' => [],
            'digest_frequency_hours' => 4,
            'emails' => [],
            'muted_until' => null,
            'reminder_settings' => ['task_reminder_days' => [7, 3, 1], 'meeting_reminder_hours' => [24, 1]],
        ]);
    });

    test('a deselected reminder interval stays deselected', function (): void {
        $user = User::factory()->create(['notification_preferences' => [
            'reminder_settings' => ['meeting_reminder_hours' => [24], 'task_reminder_days' => []],
        ]]);

        expect($user->getMeetingReminderHours())->toBe([24])
            ->and($user->getTaskReminderDays())->toBe([]);
    });

    test('keys from the retired category matrix are ignored', function (): void {
        $user = User::factory()->create(['notification_preferences' => [
            'channels' => ['task' => ['email_digest' => false]],
        ]]);

        expect($user->notification_preferences)->not->toHaveKey('channels')
            ->and($user->emailDeliveryFor(NotificationType::TaskReminder))->toBe(EmailDelivery::Immediate);
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

describe('per-type delivery', function (): void {
    test('a type without an override uses its defaults', function (): void {
        $user = User::factory()->create();

        expect($user->emailDeliveryFor(NotificationType::ApprovalRequested))->toBe(EmailDelivery::Immediate)
            ->and($user->emailDeliveryFor(NotificationType::MeetingCreated))->toBe(EmailDelivery::Digest)
            ->and($user->wantsPushFor(NotificationType::ApprovalRequested))->toBeTrue()
            ->and($user->wantsPushFor(NotificationType::MeetingCreated))->toBeFalse();
    });

    test('stored overrides win over defaults', function (): void {
        $user = $this->createUserWithTypePreference(NotificationType::MeetingCreated, ['email' => 'off', 'push' => true]);

        expect($user->emailDeliveryFor(NotificationType::MeetingCreated))->toBe(EmailDelivery::Off)
            ->and($user->wantsPushFor(NotificationType::MeetingCreated))->toBeTrue();
    });

    test('an unknown stored value falls back to the default', function (): void {
        $user = $this->createUserWithTypePreference(NotificationType::MeetingCreated, ['email' => 'weekly', 'push' => 'yes']);

        expect($user->emailDeliveryFor(NotificationType::MeetingCreated))->toBe(EmailDelivery::Digest)
            ->and($user->wantsPushFor(NotificationType::MeetingCreated))->toBeFalse();
    });
});

describe('notification emails', function (): void {
    test('with nothing chosen, mail goes to the current duty address', function (): void {
        $user = $this->createUserWithPreferences();
        $user->current_duties()->first()->update(['email' => 'pirmininkas@gmc.vusa.lt']);

        expect($user->notificationEmails())->toBe(['pirmininkas@gmc.vusa.lt']);
    });

    test('with no duty address, mail goes to the personal address', function (): void {
        $user = $this->createUserWithPreferences();
        $user->current_duties()->first()->update(['email' => null]);

        expect($user->notificationEmails())->toBe([$user->email]);
    });

    test('chosen addresses that are no longer available fall back to the same default', function (): void {
        $user = $this->createUserWithPreferences(['emails' => ['old-role@vusa.lt']]);
        $user->current_duties()->first()->update(['email' => 'pirmininkas@vusa.lt']);

        expect($user->notificationEmails())->toBe(['pirmininkas@vusa.lt']);
    });

    test('chosen available addresses are used as they are', function (): void {
        $user = $this->createUserWithPreferences();
        $user->current_duties()->first()->update(['email' => 'pirmininkas@vusa.lt']);
        $user->update(['notification_preferences' => ['emails' => [$user->email]]]);

        expect($user->fresh()->notificationEmails())->toBe([$user->email]);
    });
});
