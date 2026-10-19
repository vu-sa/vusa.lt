<?php

use App\Enums\InstitutionActivityCampaign;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    config(['queue.default' => 'sync']);
});

/**
 * Create an active institution with a 30-day meeting periodicity and one representative.
 */
function institutionWithRepresentative(): Institution
{
    $tenant = Tenant::query()->first() ?? Tenant::factory()->create();

    $institution = Institution::factory()
        ->for($tenant)
        ->create([
            'is_active' => true,
            'meeting_periodicity_days' => 30,
        ]);

    $studentRepType = DutyType::query()->where('slug', 'studentu-atstovai')->first()
        ?? DutyType::factory()->create(['slug' => 'studentu-atstovai']);

    $duty = Duty::factory()
        ->for($institution)
        ->hasAttached($studentRepType, [], 'types')
        ->create();

    User::factory()->create()->duties()->attach($duty, [
        'start_date' => now()->subYear(),
        'end_date' => null,
    ]);

    return $institution;
}

function periodicityGapTaskFor(Institution $institution): ?Task
{
    return Task::query()
        ->where('taskable_type', MorphMap::alias(Institution::class))
        ->where('taskable_id', $institution->id)
        ->where('action_type', ActionType::PeriodicityGap)
        ->first();
}

describe('tasks:repopulate institution', function (): void {
    test('waits through check-in grace then asks once with a seven-day task deadline', function (): void {
        $this->travelTo('2026-11-20 12:00:00');
        $institution = institutionWithRepresentative();
        InstitutionCheckIn::factory()->for($institution)->create(['start_date' => '2026-09-01', 'end_date' => '2026-11-17']);

        $this->artisan('tasks:repopulate institution --force')->assertSuccessful();
        expect(periodicityGapTaskFor($institution))->toBeNull();

        $this->travelTo('2026-12-04 12:00:00');
        $this->artisan('tasks:repopulate institution --force')->assertSuccessful();
        $this->artisan('tasks:repopulate institution --force')->assertSuccessful();

        $task = periodicityGapTaskFor($institution);
        expect($task)->not->toBeNull()
            ->and($task->due_date->toDateString())->toBe('2026-12-11')
            ->and(InstitutionActivityRequest::query()->where('task_id', $task->id)->count())->toBe(1);
    });

    test('reconciles stale open periodicity tasks during check-in grace', function (): void {
        $this->travelTo('2026-11-20 12:00:00');
        $institution = institutionWithRepresentative();
        InstitutionCheckIn::factory()->for($institution)->create(['start_date' => '2026-09-01', 'end_date' => '2026-11-17']);
        $users = $institution->duties->flatMap->users;
        $task = app(PeriodicityGapTaskHandler::class)->findOrCreate($institution, $users, today()->subDay());

        $this->artisan('tasks:repopulate institution --force')->assertSuccessful();

        expect($task->fresh()->completed_at)->not->toBeNull();
        Notification::assertNotSentTo($users, InstitutionActivityNotification::class);
    });

    test('no-history institutions create a task when approaching and ask each representative after their own interval', function (): void {
        $this->travelTo('2026-09-01 12:00:00');
        $institution = institutionWithRepresentative();
        $rep = $institution->duties->flatMap->users->sole();
        $rep->duties()->updateExistingPivot($institution->duties->sole()->id, ['start_date' => '2026-09-01']);
        $newRep = User::factory()->create();
        $newRep->duties()->attach($institution->duties->sole(), ['start_date' => '2026-09-15']);

        $this->travelTo('2026-09-25 12:00:00');
        $this->artisan('tasks:repopulate institution --force')->assertSuccessful();
        expect(periodicityGapTaskFor($institution))->not->toBeNull();
        Notification::assertNotSentTo([$rep, $newRep], InstitutionActivityNotification::class);

        $this->travelTo('2026-10-01 12:00:00');
        $this->artisan('tasks:repopulate institution --force')->assertSuccessful();
        expect(InstitutionActivityRequest::query()->where('institution_id', $institution->id)->count())->toBe(0);
        $this->travelTo('2026-10-02 12:00:00');
        $this->artisan('tasks:repopulate institution --force')->assertSuccessful();
        Notification::assertSentToTimes($rep, InstitutionActivityNotification::class, 1);
        Notification::assertNotSentTo($newRep, InstitutionActivityNotification::class);

        $this->travelTo('2026-10-16 12:00:00');
        $this->artisan('tasks:repopulate institution --force')->assertSuccessful();
        Notification::assertSentToTimes($rep, InstitutionActivityNotification::class, 1);
        Notification::assertSentToTimes($newRep, InstitutionActivityNotification::class, 1);
    });

    test('creates a periodicity gap task when the institution is approaching its threshold', function (): void {
        // No vacation between the meeting and today: 26 calendar days of a 30-day periodicity.
        $this->travelTo('2025-11-15');

        $institution = institutionWithRepresentative();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-10-20 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->not->toBeNull();
    });

    test('does not create a task for an inactive institution', function (): void {
        $this->travelTo('2025-11-15');

        $institution = institutionWithRepresentative();
        $institution->update(['is_active' => false]);

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-10-01 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->toBeNull();
    });

    test('does not create a task when the gap is made up of vacation days', function (): void {
        // June 20 -> September 1 is 73 calendar days, but 62 of them are summer
        // vacation, leaving 11 effective days - well inside the 30-day periodicity.
        $this->travelTo('2025-09-01');

        $institution = institutionWithRepresentative();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-06-20 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->toBeNull();
    });

    test('does not create a task while an upcoming meeting covers the institution', function (): void {
        $this->travelTo('2025-11-15');

        $institution = institutionWithRepresentative();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-10-01 10:00:00']);
        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-11-20 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->toBeNull();
    });

    test('does not create a task while an active activity report covers the institution', function (): void {
        $this->travelTo('2025-11-15');

        $institution = institutionWithRepresentative();
        $representative = $institution->duties->flatMap->users->first();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-10-01 10:00:00']);
        InstitutionCheckIn::factory()
            ->for($institution)
            ->for($representative)
            ->for($institution->tenant)
            ->create([
                'start_date' => '2025-11-01',
                'end_date' => '2025-11-30',
            ]);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->toBeNull();
    });

    test('does not ask again the morning after an activity report ends', function (): void {
        $this->travelTo('2025-11-15');

        $institution = institutionWithRepresentative();
        $representative = $institution->duties->flatMap->users->first();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-09-10 10:00:00']);
        InstitutionCheckIn::factory()
            ->for($institution)
            ->for($representative)
            ->for($institution->tenant)
            ->create(['start_date' => '2025-09-11', 'end_date' => '2025-11-14']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->toBeNull();
        Notification::assertNothingSentTo($representative);
    });

    test('creates the task when approaching and asks once, when the institution turns overdue', function (): void {
        $this->travelTo('2025-11-01');

        $institution = institutionWithRepresentative();
        $representative = $institution->duties->flatMap->users->first();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-10-05 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->not->toBeNull();
        Notification::assertNotSentTo($representative, InstitutionActivityNotification::class);

        foreach (['2025-11-05', '2025-11-06'] as $day) {
            $this->travelTo($day);
            $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);
        }

        Notification::assertSentToTimes($representative, InstitutionActivityNotification::class, 1);
        expect(InstitutionActivityRequest::query()->where('task_id', periodicityGapTaskFor($institution)->id)->count())->toBe(1);
    });

    test('asks a representative about all their overdue institutions in one send and one email', function (): void {
        $this->travelTo('2025-11-06');

        $quiet = institutionWithRepresentative();
        $representative = $quiet->duties->flatMap->users->first();
        $withRecords = institutionWithRepresentative();
        $representative->duties()->attach($withRecords->duties->first(), ['start_date' => now()->subYear(), 'end_date' => null]);
        Meeting::factory()->hasAttached($withRecords)->create(['start_time' => '2025-10-01 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        $requests = InstitutionActivityRequest::query()->where('recipient_id', $representative->id)->get();
        expect($requests->pluck('institution_id')->sort()->values()->all())->toBe(collect([$quiet->id, $withRecords->id])->sort()->values()->all())
            ->and($requests->pluck('send_id')->unique())->toHaveCount(1)
            ->and($requests->firstWhere('institution_id', $quiet->id)->campaign_type)->toBe(InstitutionActivityCampaign::ActivityConfirmation)
            ->and($requests->firstWhere('institution_id', $withRecords->id)->campaign_type)->toBe(InstitutionActivityCampaign::MissingMeetings)
            ->and(InstitutionActivityRequest::query()->distinct()->count('send_id'))->toBe(1);
        Notification::assertSentToTimes($representative, InstitutionActivityNotification::class, 1);
    });

    test('sets a due date that does not fall inside a vacation period', function (): void {
        // Overdue in 5 effective days, due a week after that: summer vacation pushes it past August 31.
        $this->travelTo('2025-06-28');

        $institution = institutionWithRepresentative();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-06-02 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        $task = periodicityGapTaskFor($institution);

        expect($task)->not->toBeNull()
            ->and($task->due_date->toDateString())->toBe('2025-09-10');
    });
});
