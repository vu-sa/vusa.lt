<?php

use App\Enums\EmailDelivery;
use App\Enums\NotificationType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\NotificationAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = makeUser(Tenant::query()->first());
});

function settingsTypesFor(User $user): array
{
    return array_map(fn (NotificationType $type): string => $type->value, app(NotificationAudience::class)->typesFor($user));
}

describe('which notifications a user sees', function (): void {
    test('a rep sees their own tasks and meetings, but not approvals or registrations', function (): void {
        $types = settingsTypesFor($this->user);

        expect($types)->toContain('task_reminder', 'meeting_reminder', 'comment_mention', 'followed_institution_activity')
            ->not->toContain('approval_requested', 'member_registration', 'welcome', 'test_push');
    });

    test('a resource manager also sees approval requests', function (): void {
        $manager = makeTenantUserWithRole('Išteklių administratorius');

        expect(settingsTypesFor($manager))->toContain('approval_requested');
    });

    test('a super admin sees every configurable notification', function (): void {
        expect(settingsTypesFor(makeAdminUser()))
            ->toBe(array_map(fn (NotificationType $type): string => $type->value, NotificationType::configurable()));
    });

    test('the page lists only those types, with the user\'s current choices', function (): void {
        $this->user->update(['notification_preferences' => ['types' => ['task_reminder' => ['email' => 'off']]]]);

        asUser($this->user)
            ->get(route('profile.notifications'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowNotificationSettings')
                ->where('notificationTypes', fn ($types): bool => collect($types)->pluck('value')->doesntContain('approval_requested'))
                ->where('notificationTypes', fn ($types): bool => collect($types)->firstWhere('value', 'task_reminder')['email'] === 'off')
            );
    });
});

describe('saving', function (): void {
    test('stores only what differs from each type\'s defaults', function (): void {
        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), ['types' => [
                'task_reminder' => ['email' => 'immediate', 'push' => true],
                'meeting_created' => ['email' => 'off', 'push' => false],
                'member_registration' => ['email' => 'off', 'push' => false],
            ]])
            ->assertSessionHas('success');

        expect($this->user->refresh()->notification_preferences['types'])->toBe(['meeting_created' => ['email' => 'off']])
            ->and($this->user->emailDeliveryFor(NotificationType::MemberRegistration))->toBe(EmailDelivery::Immediate);
    });

    test('a deselected reminder interval is saved as deselected', function (): void {
        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), ['reminder_settings' => ['meeting_reminder_hours' => [24]]])
            ->assertSessionHas('success');

        expect($this->user->refresh()->getMeetingReminderHours())->toBe([24]);
    });

    test('rejects unknown types, email modes, frequencies and intervals', function (): void {
        asUser($this->user)
            ->patch(route('profile.updateNotificationPreferences'), [
                'types' => ['not_a_type' => ['email' => 'off'], 'task_reminder' => ['email' => 'weekly']],
                'digest_frequency_hours' => 7,
                'reminder_settings' => ['task_reminder_days' => [5]],
            ])
            ->assertSessionHasErrors(['types', 'types.task_reminder.email', 'digest_frequency_hours', 'reminder_settings.task_reminder_days.0']);
    });
});

describe('muting', function (): void {
    test('applies at once and can be lifted', function (): void {
        asUser($this->user)->patch(route('profile.muteNotifications'), ['hours' => 4])->assertSessionHas('success');
        expect($this->user->refresh()->isGloballyMuted())->toBeTrue();

        asUser($this->user)->patch(route('profile.muteNotifications'), ['hours' => null])->assertSessionHas('success');
        expect($this->user->refresh()->isGloballyMuted())->toBeFalse();
    });

    test('only offers the listed durations', function (): void {
        asUser($this->user)->patch(route('profile.muteNotifications'), ['hours' => 10000])->assertSessionHasErrors('hours');
    });
});

describe('converting the old category preferences', function (): void {
    test('an unticked category channel turns that channel off for every type in its section', function (): void {
        DB::table('users')->where('id', $this->user->id)->update(['notification_preferences' => json_encode([
            'channels' => [
                'task' => ['in_app' => true, 'push' => false, 'email_digest' => true],
                'registration' => ['in_app' => true, 'push' => true, 'email_digest' => false],
                'meeting' => ['in_app' => false, 'push' => true, 'email_digest' => false],
            ],
            'followed_institutions' => ['push' => false],
            'digest_emails' => ['duty@vusa.lt'],
            'digest_frequency_hours' => 12,
            'muted_threads' => [['model_class' => 'Task', 'model_id' => '1', 'until' => null]],
        ])]);

        $migration = require database_path('migrations/2026_09_25_214955_convert_notification_preferences_to_types.php');
        $migration->up();

        $stored = json_decode(DB::table('users')->where('id', $this->user->id)->value('notification_preferences'), true);

        expect($stored['types'])->toMatchArray([
            'task_reminder' => ['push' => false],
            'meeting_reminder' => ['email' => 'off'],
            'meeting_created' => ['email' => 'off'],
            'followed_institution_activity' => ['email' => 'off', 'push' => false],
        ])
            // Overdue does not push by default, so turning it off is no override.
            ->and($stored['types'])->not->toHaveKeys(['task_assigned', 'task_overdue', 'member_registration'])
            ->and($stored['emails'])->toBe(['duty@vusa.lt'])
            ->and($stored['digest_frequency_hours'])->toBe(12)
            ->and($stored)->not->toHaveKeys(['channels', 'muted_threads', 'followed_institutions', 'digest_emails']);
    });
});

describe('restoring defaults', function (): void {
    test('clears type choices, reminders and frequency, but keeps addresses and an active mute', function (): void {
        $mutedUntil = now()->addHour()->toIso8601String();
        $this->user->update(['notification_preferences' => [
            'types' => ['task_reminder' => ['email' => 'off']],
            'reminder_settings' => ['meeting_reminder_hours' => [12]],
            'digest_frequency_hours' => 24,
            'emails' => [$this->user->email],
            'muted_until' => $mutedUntil,
        ]]);

        asUser($this->user)->delete(route('profile.resetNotificationPreferences'))->assertSessionHas('success');

        $user = $this->user->refresh();

        expect($user->emailDeliveryFor(NotificationType::TaskReminder))->toBe(EmailDelivery::Immediate)
            ->and($user->getMeetingReminderHours())->toBe([24, 1])
            ->and($user->getDigestFrequencyHours())->toBe(4)
            ->and($user->notification_preferences['emails'])->toBe([$this->user->email])
            ->and($user->isGloballyMuted())->toBeTrue();
    });
});
