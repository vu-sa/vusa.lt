<?php

use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionAdministrator;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MeetingReminderNotification;
use App\Services\CommentableMentionResolver;
use App\Services\InstitutionAccessService;
use App\Services\ResourceServices\DutyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

pest()->use(RefreshDatabase::class);

/**
 * Notifications used to reach anyone who had *ever* held a duty in the institution
 * (SendMeetingReminders flatMapped `$duty->users`, CommentableMentionResolver read the
 * all-time `Institution::users()`). These lock in the date scoping.
 */
beforeEach(function (): void {
    $this->travelTo('2025-11-15 10:00:00');

    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create();

    $this->current = User::factory()->create(['notification_preferences' => []]);
    $this->current->duties()->attach($this->duty, ['start_date' => now()->subYear(), 'end_date' => null]);

    $this->departed = User::factory()->create(['notification_preferences' => []]);
    $this->departed->duties()->attach($this->duty, [
        'start_date' => now()->subYears(4),
        'end_date' => now()->subYears(3),
    ]);
});

describe('meeting reminders', function (): void {
    beforeEach(function (): void {
        Notification::fake();

        $this->current->setMeetingReminderHours([6]);
        $this->departed->setMeetingReminderHours([6]);

        $this->meeting = Meeting::factory()
            ->hasAttached($this->institution)
            ->create(['start_time' => now()->addHours(6)]);
    });

    test('reach a member active at the meeting date', function (): void {
        $this->artisan('notifications:meeting-reminders')->assertExitCode(0);

        Notification::assertSentTo($this->current, MeetingReminderNotification::class);
    });

    test('do not reach someone whose duty ended years before the meeting', function (): void {
        $this->artisan('notifications:meeting-reminders')->assertExitCode(0);

        Notification::assertNotSentTo($this->departed, MeetingReminderNotification::class);
    });

    test('reach a nominated administrator who holds no duty at all', function (): void {
        $cadence = Cadence::factory()->forYear(2025)->create(['institution_id' => $this->institution->id]);
        $nominee = User::factory()->create(['notification_preferences' => []]);
        $nominee->setMeetingReminderHours([6]);

        InstitutionAdministrator::create([
            'institution_id' => $this->institution->id,
            'cadence_id' => $cadence->id,
            'user_id' => $nominee->id,
        ]);

        $this->artisan('notifications:meeting-reminders')->assertExitCode(0);

        Notification::assertSentTo($nominee, MeetingReminderNotification::class);
    });
});

describe('comment and mention audiences', function (): void {
    test('exclude a departed holder and include the current one', function (): void {
        $audience = app(CommentableMentionResolver::class)
            ->audienceUsers($this->institution)
            ->pluck('id');

        expect($audience)->toContain($this->current->id)
            ->and($audience)->not->toContain($this->departed->id);
    });

    test('include an administrator who holds no duty', function (): void {
        $cadence = Cadence::factory()->forYear(2025)->create(['institution_id' => $this->institution->id]);
        $nominee = User::factory()->create();

        InstitutionAdministrator::create([
            'institution_id' => $this->institution->id,
            'cadence_id' => $cadence->id,
            'user_id' => $nominee->id,
        ]);

        expect(app(CommentableMentionResolver::class)->audienceUsers($this->institution->fresh())->pluck('id'))
            ->toContain($nominee->id);
    });

    test('a meeting audience is scoped to the meeting date, not today', function (): void {
        $oldMeeting = Meeting::factory()->hasAttached($this->institution)->create([
            'start_time' => now()->subYears(4),
        ]);

        $audience = app(CommentableMentionResolver::class)->audienceUsers($oldMeeting)->pluck('id');

        expect($audience)->toContain($this->departed->id)
            ->and($audience)->not->toContain($this->current->id);
    });
});

describe('administrators are not members', function (): void {
    beforeEach(function (): void {
        $cadence = Cadence::factory()->forYear(2025)->create(['institution_id' => $this->institution->id]);
        $this->nominee = User::factory()->create();

        InstitutionAdministrator::create([
            'institution_id' => $this->institution->id,
            'cadence_id' => $cadence->id,
            'user_id' => $this->nominee->id,
        ]);

        $this->institution->refresh();
    });

    test('they do not appear among the institution users', function (): void {
        expect($this->institution->users()->pluck('users.id'))->not->toContain($this->nominee->id);
    });

    test('they do not appear among any duty holders', function (): void {
        expect($this->institution->duties()->with('current_users')->get()
            ->pluck('current_users')->flatten()->pluck('id'))
            ->not->toContain($this->nominee->id);
    });

    test('they do not appear in the search index of current members', function (): void {
        expect($this->institution->toSearchableArray()['current_user_names'])
            ->not->toContain($this->nominee->name);
    });

    test('but they can see the institution they administer', function (): void {
        $accessible = app(InstitutionAccessService::class)
            ->getAccessibleInstitutionIds($this->nominee);

        expect($accessible)->toContain($this->institution->id);
    });

    test('and it reaches their dashboard, flagged as administered rather than held', function (): void {
        asUser($this->nominee)->get(route('dashboard.atstovavimas'));

        $institutions = DutyService::getUserInstitutionsForDashboard();

        expect($institutions->pluck('id'))->toContain($this->institution->id)
            ->and($institutions->firstWhere('id', $this->institution->id)->is_administered)->toBeTrue();
    });
});
