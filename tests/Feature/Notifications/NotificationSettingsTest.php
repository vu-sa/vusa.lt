<?php

use App\Actions\GetMeetingOverseers;
use App\Enums\EmailDelivery;
use App\Enums\NotificationType;
use App\Enums\ResponsibilityPurpose;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionSecretary;
use App\Models\Meeting;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\NotificationAudience;
use App\Settings\AtstovavimasSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        expect($types)->toContain('task_reminder', 'meeting_reminder', 'meeting_created', 'comment_mention', 'followed_institution_activity')
            ->not->toContain('meeting_agenda_completed', 'approval_requested', 'member_registration', 'institution_activity_not_mine', 'support_request_created', 'welcome', 'test_push');
    });

    test('an ordinary user has an explicit policy for every notification type', function (): void {
        $audience = app(NotificationAudience::class);

        foreach (NotificationType::cases() as $type) {
            expect($audience->canReceive($this->user, $type))->toBeBool();
        }

        expect($audience->canReceive($this->user, NotificationType::ReservationDraftItemTaken))->toBeTrue();
    });

    test('meeting overseers from coordination grants and visibility roles can configure oversight notices', function (): void {
        $institution = Institution::factory()->for(Tenant::query()->first())->create();
        $meeting = Meeting::factory()->hasAttached($institution)->create();
        $tenantRole = Role::factory()->create();
        $globalRole = Role::factory()->create();
        $settings = Mockery::mock(AtstovavimasSettings::class)->makePartial();
        $settings->shouldReceive('getTenantVisibilityRoleIds')->andReturn(collect([$tenantRole->id]));
        $settings->shouldReceive('getGlobalVisibilityRoleIds')->andReturn(collect([$globalRole->id]));
        app()->instance(AtstovavimasSettings::class, $settings);

        $coordinationDuty = Duty::factory()->for($institution)->create();
        grantPurpose($coordinationDuty, ResponsibilityPurpose::StudentRepCoordination);
        $coordinator = User::factory()->create();
        $coordinator->duties()->attach($coordinationDuty, ['start_date' => today()]);

        $visibilityDuty = Duty::factory()->for($institution)->create();
        $visibilityDuty->assignRole($tenantRole);
        $tenantOverseer = User::factory()->create();
        $tenantOverseer->duties()->attach($visibilityDuty, ['start_date' => today()]);
        $globalOverseer = User::factory()->create()->assignRole($globalRole);

        $overseers = GetMeetingOverseers::execute($meeting)->pluck('id');
        foreach ([$coordinator, $tenantOverseer, $globalOverseer] as $user) {
            expect($overseers)->toContain($user->id)
                ->and(settingsTypesFor($user))->toContain('meeting_created', 'meeting_agenda_completed');
        }
    });

    test('secretary settings follow the effective term today while meeting oversight uses the meeting date', function (): void {
        $this->travelTo(now()->setDate(2032, 8, 15));
        $institution = Institution::factory()->for(Tenant::query()->first())->create();
        $shared = Cadence::factory()->forYear(2032)->create();
        $current = Cadence::factory()->forYear(2032)->for($institution)->create();
        $past = Cadence::factory()->forYear(2031)->for($institution)->create();
        $currentSecretary = User::factory()->create();
        $historicalSecretary = User::factory()->create();
        $sharedNominee = User::factory()->create();

        foreach ([[$current, $currentSecretary], [$past, $historicalSecretary], [$shared, $sharedNominee]] as [$cadence, $user]) {
            InstitutionSecretary::create([
                'institution_id' => $institution->id,
                'cadence_id' => $cadence->id,
                'user_id' => $user->id,
            ]);
        }

        $currentMeeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => now()]);
        $pastMeeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->subYear()]);

        expect(GetMeetingOverseers::execute($currentMeeting)->pluck('id'))->toContain($currentSecretary->id)
            ->not->toContain($sharedNominee->id)
            ->and(settingsTypesFor($currentSecretary))->toContain('meeting_created', 'meeting_agenda_completed')
            ->and(settingsTypesFor($sharedNominee))->not->toContain('meeting_created', 'meeting_agenda_completed')
            ->and(GetMeetingOverseers::execute($pastMeeting)->pluck('id'))->toContain($historicalSecretary->id)
            ->and(settingsTypesFor($historicalSecretary))->not->toContain('meeting_created', 'meeting_agenda_completed');
    });

    test('a resource manager also sees approval requests', function (): void {
        $manager = makeTenantUserWithRole('Išteklių administratorius');
        grantPurpose($manager->current_duties()->first(), ResponsibilityPurpose::ReservationHandling);

        expect(settingsTypesFor($manager))->toContain('approval_requested');
    });

    test('a current coordinator sees student representative registration and meeting notifications', function (): void {
        $duty = $this->user->duties()->firstOrFail();
        ResponsibilityGrant::factory()->forDuty($duty)->forTenant($duty->institution->tenant)->create();

        expect(settingsTypesFor($this->user))->toContain('student_rep_registration', 'meeting_created', 'institution_activity_not_mine');
    });

    test('whoever may ask about meetings by updating institutions hears when the email reached the wrong person', function (): void {
        expect(settingsTypesFor(makeTenantUserWithRole('Komunikacijos koordinatorius')))->toContain('institution_activity_not_mine');
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

describe('restoring defaults', function (): void {
    test('clears type choices, reminders and digest schedule, but keeps addresses and an active mute', function (): void {
        $mutedUntil = now()->addHour()->toIso8601String();
        $this->user->update(['notification_preferences' => [
            'types' => ['task_reminder' => ['email' => 'off']],
            'reminder_settings' => ['meeting_reminder_hours' => [12]],
            'digest_frequency_hours' => 4,
            'digest_send_hour' => 18,
            'emails' => [$this->user->email],
            'muted_until' => $mutedUntil,
        ]]);

        asUser($this->user)->delete(route('profile.resetNotificationPreferences'))->assertSessionHas('success');

        $user = $this->user->refresh();

        expect($user->emailDeliveryFor(NotificationType::TaskReminder))->toBe(EmailDelivery::Immediate)
            ->and($user->getMeetingReminderHours())->toBe([24])
            ->and($user->getTaskReminderDays())->toBe([3])
            ->and($user->getDigestFrequencyHours())->toBe(24)
            ->and($user->getDigestSendHour())->toBe(15)
            ->and($user->notification_preferences['emails'])->toBe([$this->user->email])
            ->and($user->isGloballyMuted())->toBeTrue();
    });
});

test('task reminders require exactly one supported day', function (array $days, string $error): void {
    asUser($this->user)->patch(route('profile.updateNotificationPreferences'), [
        'reminder_settings' => ['task_reminder_days' => $days],
    ])->assertSessionHasErrors($error);
})->with([
    'seven days' => [[7], 'reminder_settings.task_reminder_days.0'],
    'two choices' => [[3, 1], 'reminder_settings.task_reminder_days'],
    'no choice' => [[], 'reminder_settings.task_reminder_days'],
]);

test('saves one selected task reminder day', function (int $days): void {
    asUser($this->user)->patch(route('profile.updateNotificationPreferences'), [
        'reminder_settings' => ['task_reminder_days' => [$days]],
    ])->assertSessionHas('success');

    expect($this->user->refresh()->getTaskReminderDays())->toBe([$days]);
})->with([3, 1]);

test('digest settings reject retired frequencies and hours outside daytime', function (string $key, int $value): void {
    asUser($this->user)->patch(route('profile.updateNotificationPreferences'), [$key => $value])
        ->assertSessionHasErrors($key);
})->with([
    'hourly' => ['digest_frequency_hours', 1],
    'twelve hours' => ['digest_frequency_hours', 12],
    'before daytime' => ['digest_send_hour', 6],
    'quiet hours' => ['digest_send_hour', 22],
]);

test('saves both digest modes and keeps the chosen hour between mode changes', function (): void {
    asUser($this->user)->patch(route('profile.updateNotificationPreferences'), [
        'digest_frequency_hours' => 24, 'digest_send_hour' => 18,
    ])->assertSessionHas('success');
    expect($this->user->refresh()->getDigestSendHour())->toBe(18);

    asUser($this->user)->patch(route('profile.updateNotificationPreferences'), ['digest_frequency_hours' => 4])
        ->assertSessionHas('success');
    expect($this->user->refresh()->getDigestFrequencyHours())->toBe(4)
        ->and($this->user->getDigestSendHour())->toBe(18);

    asUser($this->user)->get(route('profile.notifications'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('notificationPreferences.digest_send_hour', 18));
});

test('daily delivery accepts the daytime boundary hours', function (int $hour): void {
    asUser($this->user)->patch(route('profile.updateNotificationPreferences'), ['digest_send_hour' => $hour])
        ->assertSessionHas('success');
    expect($this->user->refresh()->getDigestSendHour())->toBe($hour);
})->with([7, 21]);
