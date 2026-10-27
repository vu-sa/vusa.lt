<?php

use App\Actions\ActivityRequests\AnswerInstitutionActivityRequest;
use App\Actions\ActivityRequests\ResolveActivityRequestEligibility;
use App\Enums\InstitutionActivityAnswer;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\InstitutionActivityStatus;
use App\Enums\NotificationType;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\NotificationDigestQueue;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

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

    $duty = Duty::factory()->for($institution)->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);

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

        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        expect(periodicityGapTaskFor($institution))->toBeNull();

        $this->travelTo('2026-12-04 12:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        $this->artisan('tasks:repopulate institution')->assertSuccessful();

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

        $this->artisan('tasks:repopulate institution')->assertSuccessful();

        expect($task->fresh()->completed_at)->not->toBeNull()
            ->and($task->fresh()->isCancelled())->toBeFalse();
        Notification::assertNotSentTo($users, InstitutionActivityNotification::class);
    });

    test('no-history institutions create a task when approaching and ask each representative after their own interval, a month apart', function (): void {
        $this->travelTo('2026-09-01 12:00:00');
        $institution = institutionWithRepresentative();
        $rep = $institution->duties->flatMap->users->sole();
        $rep->duties()->updateExistingPivot($institution->duties->sole()->id, ['start_date' => '2026-09-01']);
        $newRep = User::factory()->create();
        $newRep->duties()->attach($institution->duties->sole(), ['start_date' => '2026-09-15']);

        $this->travelTo('2026-09-25 12:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        expect(periodicityGapTaskFor($institution))->not->toBeNull();
        Notification::assertNotSentTo([$rep, $newRep], InstitutionActivityNotification::class);

        $this->travelTo('2026-10-01 12:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        expect(InstitutionActivityRequest::query()->where('institution_id', $institution->id)->count())->toBe(0);
        $this->travelTo('2026-10-02 12:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        Notification::assertSentToTimes($rep, InstitutionActivityNotification::class, 1);
        Notification::assertNotSentTo($newRep, InstitutionActivityNotification::class);

        // Their own interval has passed, but the institution was asked about less than a month ago.
        $this->travelTo('2026-10-16 12:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        Notification::assertNotSentTo($newRep, InstitutionActivityNotification::class);

        $this->travelTo('2026-11-03 12:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
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

        $this->artisan('tasks:repopulate institution')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->not->toBeNull();
    });

    test('does not create a task for an inactive institution', function (): void {
        $this->travelTo('2025-11-15');

        $institution = institutionWithRepresentative();
        $institution->update(['is_active' => false]);

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-10-01 10:00:00']);

        $this->artisan('tasks:repopulate institution')->assertExitCode(0);

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

        $this->artisan('tasks:repopulate institution')->assertExitCode(0);

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

        $this->artisan('tasks:repopulate institution')->assertExitCode(0);

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

        $this->artisan('tasks:repopulate institution')->assertExitCode(0);

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

        $this->artisan('tasks:repopulate institution')->assertExitCode(0);

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

        $this->artisan('tasks:repopulate institution')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->not->toBeNull();
        Notification::assertNotSentTo($representative, InstitutionActivityNotification::class);

        foreach (['2025-11-05', '2025-11-06'] as $day) {
            $this->travelTo($day);
            $this->artisan('tasks:repopulate institution')->assertExitCode(0);
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

        $this->artisan('tasks:repopulate institution')->assertExitCode(0);

        $requests = InstitutionActivityRequest::query()->where('recipient_id', $representative->id)->get();
        expect($requests->pluck('institution_id')->sort()->values()->all())->toBe(collect([$quiet->id, $withRecords->id])->sort()->values()->all())
            ->and($requests->pluck('send_id')->unique())->toHaveCount(1)
            ->and($requests->firstWhere('institution_id', $quiet->id)->campaign_type)->toBe(InstitutionActivityCampaign::ActivityConfirmation)
            ->and($requests->firstWhere('institution_id', $withRecords->id)->campaign_type)->toBe(InstitutionActivityCampaign::MissingMeetings)
            ->and(InstitutionActivityRequest::query()->distinct()->count('send_id'))->toBe(1);
        Notification::assertSentToTimes($representative, InstitutionActivityNotification::class, 1);
    });

    test('an open task follows the current representatives, so they are the ones asked', function (): void {
        $this->travelTo('2025-11-06 12:00:00');
        $institution = institutionWithRepresentative();
        $duty = $institution->duties->sole();
        $formerRep = $duty->users->sole();
        Meeting::factory()->hasAttached($institution)->create(['start_time' => '2025-09-01 10:00:00']);
        $task = app(PeriodicityGapTaskHandler::class)->findOrCreate($institution, collect([$formerRep]), today()->addWeek());
        $formerRep->duties()->updateExistingPivot($duty->id, ['end_date' => today()->subDays(10)]);
        $currentRep = User::factory()->create();
        $currentRep->duties()->attach($duty, ['start_date' => today()->subDays(9)]);

        $this->artisan('tasks:repopulate institution')->assertSuccessful();

        expect($task->users()->pluck('users.id')->all())->toBe([$currentRep->id]);
        Notification::assertSentToTimes($currentRep, InstitutionActivityNotification::class, 1);
        Notification::assertNotSentTo($formerRep, InstitutionActivityNotification::class);
    });

    test('asks automatically only a month after any earlier question about the institution', function (?string $answer): void {
        $this->travelTo('2025-11-06 12:00:00');
        $institution = institutionWithRepresentative();
        $rep = $institution->duties->flatMap->users->sole();
        Meeting::factory()->hasAttached($institution)->create(['start_time' => '2025-09-01 10:00:00']);
        $earlier = InstitutionActivityRequest::factory()->create([
            'institution_id' => $institution->id, 'recipient_id' => $rep->id, 'requested_by_id' => User::factory()->create()->id,
            'period_start' => '2025-09-02', 'period_end' => '2025-10-17', 'expires_at' => today()->subDay(),
            'answer' => $answer, 'answered_at' => $answer === null ? null : now()->subDays(19),
        ]);
        $earlier->forceFill(['created_at' => now()->subDays(20)])->save();

        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        expect(InstitutionActivityRequest::query()->count())->toBe(1);
        Notification::assertNothingSentTo($rep);

        $this->travelTo('2025-11-18 12:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        Notification::assertSentToTimes($rep, InstitutionActivityNotification::class, 1);
    })->with(['expired unanswered' => [null], 'answered not mine' => ['not_mine']]);

    test('after "no meeting until today" a coordinator may ask from tomorrow, the automatic question waits a month', function (): void {
        $this->travelTo('2025-11-06 12:00:00');
        $institution = institutionWithRepresentative();
        $rep = $institution->duties->flatMap->users->sole();
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        app(AnswerInstitutionActivityRequest::class)->execute(InstitutionActivityRequest::query()->sole(), InstitutionActivityAnswer::NotMet, until: today());

        $eligibility = app(ResolveActivityRequestEligibility::class);
        $statusOn = function (string $day) use ($institution, $eligibility): InstitutionActivityStatus {
            $this->travelTo($day.' 12:00:00');

            return $eligibility->status($institution->fresh(['meetings', 'checkIns', 'types']))->status;
        };

        expect($statusOn('2025-11-07'))->toBe(InstitutionActivityStatus::RecentCheckIn)
            ->and($eligibility->skipReason($institution->fresh(['meetings', 'checkIns', 'types']), manual: true))->toBeNull();
        $this->artisan('tasks:repopulate institution')->assertSuccessful();

        // Half the interval later the institution is overdue again, but it was asked less than a month ago.
        expect($statusOn('2025-11-28'))->toBe(InstitutionActivityStatus::Overdue);
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        Notification::assertSentToTimes($rep, InstitutionActivityNotification::class, 1);

        $this->travelTo('2025-12-07 12:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        Notification::assertSentToTimes($rep, InstitutionActivityNotification::class, 2);
        expect(InstitutionActivityRequest::query()->latest('created_at')->first()->period_start->toDateString())->toBe('2025-11-07');
    });

    test('sets a due date that does not fall inside a vacation period', function (): void {
        // Overdue in 5 effective days, due a week after that: summer vacation pushes it past August 31.
        $this->travelTo('2025-06-28');

        $institution = institutionWithRepresentative();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-06-02 10:00:00']);

        $this->artisan('tasks:repopulate institution')->assertExitCode(0);

        $task = periodicityGapTaskFor($institution);

        expect($task)->not->toBeNull()
            ->and($task->due_date->toDateString())->toBe('2025-09-10');
    });
});

/**
 * Two representatives from the start of the term, one from its middle; A last reported no meetings until 20 September.
 *
 * @return array{institution: Institution, a: User, b: User, c: User}
 */
function institutionWithMidTermRepresentative(): array
{
    $institution = Institution::factory()->for(Tenant::query()->firstOrFail())->create(['is_active' => true, 'meeting_periodicity_days' => 30]);
    $duty = Duty::factory()->for($institution)->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    [$a, $b, $c] = User::factory()->count(3)->create()->all();
    $a->duties()->attach($duty, ['start_date' => '2025-09-01']);
    $b->duties()->attach($duty, ['start_date' => '2025-09-01']);
    $c->duties()->attach($duty, ['start_date' => '2025-10-15']);
    InstitutionCheckIn::factory()->for($institution)->create(['user_id' => $a->id, 'start_date' => '2025-09-01', 'end_date' => '2025-09-20']);

    return ['institution' => $institution, 'a' => $a, 'b' => $b, 'c' => $c];
}

describe('a representative who joins mid-term', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2025-11-06 12:00:00');
        ['institution' => $this->institution, 'a' => $this->a, 'b' => $this->b, 'c' => $this->c] = institutionWithMidTermRepresentative();
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        $this->requestOf = fn (User $user) => InstitutionActivityRequest::query()->where('recipient_id', $user->id)->sole();
        $this->answer = fn (User $user, InstitutionActivityAnswer $answer, array $meetings = []) => app(AnswerInstitutionActivityRequest::class)
            ->execute(($this->requestOf)($user), $answer, meetings: $meetings);
    });

    test('is asked the same day as the others, but only from their own start', function (): void {
        expect(($this->requestOf)($this->a)->period_start->toDateString())->toBe('2025-09-21')
            ->and(($this->requestOf)($this->b)->period_start->toDateString())->toBe('2025-09-21')
            ->and(($this->requestOf)($this->c)->period_start->toDateString())->toBe('2025-10-15')
            ->and(InstitutionActivityRequest::query()->distinct()->count('send_id'))->toBe(1);
        foreach ([$this->a, $this->b, $this->c] as $rep) {
            Notification::assertSentToTimes($rep, InstitutionActivityNotification::class, 1);
        }
    });

    test('an earlier colleague\'s "no meeting" also closes their question; they can still add a meeting', function (): void {
        ($this->answer)($this->a, InstitutionActivityAnswer::NotMet);

        expect(($this->requestOf)($this->c)->isOpen())->toBeFalse()
            ->and(($this->requestOf)($this->c)->acceptsMeetings())->toBeTrue()
            ->and(($this->requestOf)($this->b)->isOpen())->toBeFalse()
            ->and($this->institution->checkIns()->where('user_id', $this->a->id)->count())->toBe(2);
    });

    test('their "no meeting" covers only their own period; the colleagues can still report the rest', function (): void {
        ($this->answer)($this->c, InstitutionActivityAnswer::NotMet);

        expect(($this->requestOf)($this->a)->isOpen())->toBeTrue()
            ->and(($this->requestOf)($this->b)->isOpen())->toBeTrue();

        ($this->answer)($this->a, InstitutionActivityAnswer::NotMet);

        $periods = $this->institution->checkIns()->orderBy('start_date')->get()
            ->map(fn (InstitutionCheckIn $checkIn) => [$checkIn->user_id, $checkIn->start_date->toDateString(), $checkIn->end_date->toDateString()])->all();
        expect($periods)->toBe([
            [$this->a->id, '2025-09-01', '2025-09-20'],
            [$this->a->id, '2025-09-21', '2025-10-14'],
            [$this->c->id, '2025-10-15', '2025-11-06'],
        ])->and(($this->requestOf)($this->b)->isOpen())->toBeFalse();
    });

    test('they can cancel only their own report', function (): void {
        ($this->answer)($this->a, InstitutionActivityAnswer::NotMet);
        $colleagues = $this->institution->checkIns()->where('user_id', $this->a->id)->latest('start_date')->first();
        $own = InstitutionCheckIn::factory()->for($this->institution)->create(['user_id' => $this->c->id]);

        expect($this->c->can('delete', $colleagues))->toBeFalse()
            ->and($this->c->can('delete', $own))->toBeTrue();
    });

    test('a meeting they record shortens a colleague\'s report, but only within their own period', function (): void {
        ($this->answer)($this->a, InstitutionActivityAnswer::NotMet);

        expect(fn () => ($this->answer)($this->c, InstitutionActivityAnswer::Met, [['date' => '2025-10-01', 'type' => 'in-person', 'time' => '10:00']]))
            ->toThrow(ValidationException::class);

        ($this->answer)($this->c, InstitutionActivityAnswer::Met, [['date' => '2025-10-20', 'type' => 'in-person', 'time' => '10:00']]);

        expect($this->institution->checkIns()->whereDate('start_date', '2025-09-21')->sole()->end_date->toDateString())->toBe('2025-10-19');
    });
});

/**
 * Last term's representative left an open task behind; the institution never recorded a meeting.
 *
 * @return array{institution: Institution, task: Task, oldRep: User, duty: Duty}
 */
function staleTaskFromLastTerm(): array
{
    $institution = Institution::factory()->for(Tenant::query()->first())->create(['is_active' => true, 'meeting_periodicity_days' => 30]);
    $duty = Duty::factory()->for($institution)->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    $oldRep = User::factory()->create();
    $oldRep->duties()->attach($duty, ['start_date' => '2025-09-01', 'end_date' => '2026-06-30']);
    $task = app(PeriodicityGapTaskHandler::class)->findOrCreate($institution, collect([$oldRep]), today()->setDate(2026, 2, 15));

    return compact('institution', 'task', 'oldRep', 'duty');
}

function openGapTasksFor(Institution $institution): Collection
{
    return Task::query()->where('taskable_id', $institution->id)->where('action_type', ActionType::PeriodicityGap)->whereNull('completed_at')->with('users')->get();
}

describe('open tasks after the people or the institution change', function (): void {
    test('a new term with nothing owed yet cancels the old task silently, then gives the new rep their own', function (): void {
        $this->travelTo('2026-02-08 06:00:00');
        ['institution' => $institution, 'task' => $task, 'oldRep' => $oldRep, 'duty' => $duty] = staleTaskFromLastTerm();
        $newRep = User::factory()->create();
        $newRep->duties()->attach($duty, ['start_date' => '2026-09-15']);

        $this->travelTo('2026-10-01 10:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();

        expect($task->fresh()->isCancelled())->toBeTrue()
            ->and($task->fresh()->completed_at)->not->toBeNull()
            ->and(openGapTasksFor($institution))->toBeEmpty();
        Notification::assertNothingSent();

        // Near their own deadline the new rep gets a task, and once it passes, the question.
        $this->travelTo('2026-10-12 10:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        expect(openGapTasksFor($institution)->sole()->users->modelKeys())->toBe([$newRep->id]);
        Notification::assertNothingSent();

        $this->travelTo('2026-10-17 10:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        Notification::assertSentToTimes($newRep, InstitutionActivityNotification::class, 1);
        Notification::assertNotSentTo($oldRep, InstitutionActivityNotification::class);
    });

    test('a new rep who already owes a report takes over the old task and is the only one asked', function (): void {
        $this->travelTo('2026-02-08 06:00:00');
        ['task' => $task, 'oldRep' => $oldRep, 'duty' => $duty] = staleTaskFromLastTerm();
        $newRep = User::factory()->create();
        $newRep->duties()->attach($duty, ['start_date' => '2026-09-01']);

        $this->travelTo('2026-10-15 10:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();

        expect($task->fresh()->completed_at)->toBeNull()
            ->and($task->fresh()->users->modelKeys())->toBe([$newRep->id]);
        Notification::assertSentToTimes($newRep, InstitutionActivityNotification::class, 1);
        Notification::assertNotSentTo($oldRep, InstitutionActivityNotification::class);
    });

    test('with nobody in office the task stays open for coordinators and nobody is emailed', function (): void {
        $this->travelTo('2026-02-08 06:00:00');
        ['institution' => $institution, 'task' => $task] = staleTaskFromLastTerm();

        $this->travelTo('2026-10-15 10:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();

        expect($task->fresh()->completed_at)->toBeNull()
            ->and(InstitutionActivityRequest::query()->where('institution_id', $institution->id)->exists())->toBeFalse();
        Notification::assertNothingSent();
    });

    test('a deactivated institution cancels its task and the question it already sent stops waiting', function (): void {
        $this->travelTo('2026-10-15 10:00:00');
        $institution = institutionWithRepresentative();
        $rep = $institution->duties->flatMap->users->sole();
        $this->artisan('tasks:repopulate institution')->assertSuccessful();
        $task = periodicityGapTaskFor($institution);
        expect(InstitutionActivityRequest::query()->open()->where('task_id', $task->id)->count())->toBe(1);

        $institution->update(['is_active' => false]);
        $this->travelTo('2026-10-16 10:00:00');
        $this->artisan('tasks:repopulate institution')->assertSuccessful();

        expect($task->fresh()->isCancelled())->toBeTrue()
            ->and(InstitutionActivityRequest::query()->open()->where('task_id', $task->id)->exists())->toBeFalse()
            ->and(InstitutionActivityRequest::query()->where('task_id', $task->id)->whereNotNull('resolved_at')->exists())->toBeFalse();
        Notification::assertSentToTimes($rep, InstitutionActivityNotification::class, 1);
    });

    test('a dry run reports the cancellation and reassignment but changes and sends nothing', function (): void {
        $this->travelTo('2026-02-08 06:00:00');
        ['task' => $cancellable, 'duty' => $duty] = staleTaskFromLastTerm();
        User::factory()->create()->duties()->attach($duty, ['start_date' => '2026-09-15']);
        ['task' => $reassignable, 'duty' => $otherDuty, 'oldRep' => $otherOldRep] = staleTaskFromLastTerm();
        User::factory()->create()->duties()->attach($otherDuty, ['start_date' => '2026-09-01']);

        $this->travelTo('2026-10-01 10:00:00');
        $this->artisan('tasks:repopulate institution --dry-run')
            ->expectsOutputToContain('Would mark cancelled')
            ->expectsOutputToContain('Would reassign')
            ->assertSuccessful();

        expect($cancellable->fresh()->completed_at)->toBeNull()
            ->and($reassignable->fresh()->users->modelKeys())->toBe([$otherOldRep->id])
            ->and(InstitutionActivityRequest::query()->exists())->toBeFalse();
        Notification::assertNothingSent();
    });

    test('a representative who takes these emails as a digest still gets the question', function (): void {
        Notification::swap(new ChannelManager(app()));
        $this->travelTo('2026-10-15 10:00:00');
        $institution = institutionWithRepresentative();
        $rep = $institution->duties->flatMap->users->sole();
        $rep->update(['notification_preferences' => ['types' => [NotificationType::InstitutionActivity->value => ['email' => 'digest']]]]);

        $this->artisan('tasks:repopulate institution')->assertSuccessful();

        expect(NotificationDigestQueue::query()->where('user_id', $rep->id)
            ->where('notification_class', InstitutionActivityNotification::class)->exists())->toBeTrue();
    });
});
