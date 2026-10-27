<?php

use App\Actions\GetRepOutcomeMetrics;
use App\Enums\ResponsibilityPurpose;
use App\Enums\VoteValue;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\RepMetricSnapshot;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Tasks\Enums\ActionType;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-09-20 10:00:00');

    $tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($tenant)->create();

    $duty = Duty::factory()->for($this->institution)->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);

    $this->rep = User::factory()->create(['last_action' => now()->subDay()]);
    $this->rep->duties()->attach($duty, ['start_date' => '2026-01-01', 'end_date' => null]);

    $this->idleRep = User::factory()->create(['last_action' => now()->subMonths(3)]);
    $this->idleRep->duties()->attach($duty, ['start_date' => '2026-01-01', 'end_date' => null]);
});

/** A past meeting of the institution, recorded `$recordedAfterDays` after it began, by `$recordedBy`. */
function pastMeeting(Institution $institution, string $startsAt, int $recordedAfterDays, ?User $recordedBy = null): Meeting
{
    $meeting = Meeting::factory()->hasAttached($institution)->create([
        'start_time' => $startsAt,
        'created_at' => now()->parse($startsAt)->addDays($recordedAfterDays),
    ]);

    if ($recordedBy !== null) {
        activity()->performedOn($meeting)->causedBy($recordedBy)->event('created')->log('created');
    }

    return $meeting;
}

describe('GetRepOutcomeMetrics', function (): void {
    test('counts a meeting recorded before or within a week of the sitting, not later', function (): void {
        pastMeeting($this->institution, '2026-09-10 10:00:00', 2);
        pastMeeting($this->institution, '2026-09-11 10:00:00', -1);
        pastMeeting($this->institution, '2026-09-12 10:00:00', 20);

        $report = GetRepOutcomeMetrics::execute(3);

        expect($report['meetings']['2026-09'])->toMatchArray(['total' => 2, 'withinWeek' => 2])
            ->and($report['now']['recorded_within_week'])->toBe(100.0)
            ->and($report['months'])->toBe(['2026-07', '2026-08', '2026-09']);
    });

    test('a meeting that has not happened yet is not measured', function (): void {
        pastMeeting($this->institution, '2026-09-25 10:00:00', 0);

        expect(GetRepOutcomeMetrics::execute(1)['meetings']['2026-09']['total'])->toBe(0);
    });

    test('an agenda item has vote information when a vote carries a decision or a student vote', function (): void {
        $meeting = pastMeeting($this->institution, '2026-09-10 10:00:00', 1);
        [$decided, $studentVoted, $bare, $blank] = collect(range(1, 4))->map(fn () => AgendaItem::factory()->create(['meeting_id' => $meeting->id]));

        Vote::factory()->create(['agenda_item_id' => $decided->id, 'decision' => VoteValue::Positive]);
        Vote::factory()->create(['agenda_item_id' => $studentVoted->id, 'student_vote' => VoteValue::Negative]);
        Vote::factory()->create(['agenda_item_id' => $blank->id]);

        expect(GetRepOutcomeMetrics::execute(1)['meetings']['2026-09'])->toMatchArray(['items' => 4, 'itemsWithVotes' => 2]);
    });

    test('says who recorded a meeting: the institution\'s own representative or someone else', function (): void {
        pastMeeting($this->institution, '2026-09-10 10:00:00', 1, $this->rep);
        pastMeeting($this->institution, '2026-09-11 10:00:00', 1, User::factory()->create());
        pastMeeting($this->institution, '2026-09-12 10:00:00', 1);

        expect(GetRepOutcomeMetrics::execute(1)['meetings']['2026-09'])->toMatchArray(['known' => 2, 'byRep' => 1]);
    });

    test('a representative counts only while their term covers the meeting', function (): void {
        $this->rep->dutiables()->update(['end_date' => '2026-09-05']);

        pastMeeting($this->institution, '2026-09-10 10:00:00', 1, $this->rep);

        expect(GetRepOutcomeMetrics::execute(1)['meetings']['2026-09'])->toMatchArray(['known' => 1, 'byRep' => 0]);
    });

    test('tasks are measured per type: completion, median days and the month they belong to', function (): void {
        $gap = fn (string $createdAt, ?string $completedAt) => Task::factory()->for($this->institution, 'taskable')->create([
            'action_type' => ActionType::PeriodicityGap,
            'created_at' => $createdAt,
            'completed_at' => $completedAt,
        ]);
        $gap('2026-09-01 08:00:00', '2026-09-05 08:00:00');
        $gap('2026-09-02 08:00:00', '2026-09-04 08:00:00');
        $gap('2026-09-03 08:00:00', null);
        Task::factory()->for($this->institution, 'taskable')->create(['action_type' => ActionType::AgendaCompletion, 'created_at' => '2026-08-15 08:00:00']);

        $report = GetRepOutcomeMetrics::execute(2);

        expect($report['tasks']['periodicity_gap'])->toMatchArray(['total' => 3, 'completed' => 2, 'medianDays' => 3.0])
            ->and($report['tasks']['periodicity_gap']['monthly']['2026-09'])->toBe(['total' => 3, 'completed' => 2])
            ->and($report['tasks']['agenda_completion']['monthly']['2026-08'])->toBe(['total' => 1, 'completed' => 0])
            ->and($report['now']['periodicity_gap'])->toBe(66.7)
            ->and($report['now']['task_completion'])->toBe(50.0);
    });

    test('a cancelled task counts as neither done nor missed', function (): void {
        $gap = fn (?string $completedAt, ?string $cancelledAt = null) => Task::factory()->for($this->institution, 'taskable')->create([
            'action_type' => ActionType::PeriodicityGap,
            'created_at' => '2026-09-01 08:00:00',
            'completed_at' => $completedAt,
            'cancelled_at' => $cancelledAt,
        ]);
        $gap('2026-09-05 08:00:00');
        $gap(null);
        $gap('2026-09-10 08:00:00', '2026-09-10 08:00:00');

        $report = GetRepOutcomeMetrics::execute(2);

        expect($report['tasks']['periodicity_gap'])->toMatchArray(['total' => 2, 'completed' => 1])
            ->and($report['now']['periodicity_gap'])->toBe(50.0);
    });

    test('a task that predates the window is left out', function (): void {
        Task::factory()->for($this->institution, 'taskable')->create(['action_type' => ActionType::Manual, 'created_at' => '2025-01-01 08:00:00']);

        expect(GetRepOutcomeMetrics::execute(2)['tasks'])->toBeEmpty();
    });

    test('reps active in the last 30 days are a snapshot of the people holding a rep duty', function (): void {
        User::factory()->create(['last_action' => now()]);

        expect(GetRepOutcomeMetrics::execute(1)['activeReps'])->toBe(['total' => 2, 'active' => 1])
            ->and(GetRepOutcomeMetrics::execute(1)['now']['active_reps'])->toBe(50.0);
    });

    test('with nothing to measure every rate is null, never a division by zero', function (): void {
        $now = GetRepOutcomeMetrics::execute(1)['now'];

        expect($now['recorded_within_week'])->toBeNull()
            ->and($now['vote_information'])->toBeNull()
            ->and($now['task_completion'])->toBeNull()
            ->and($now['periodicity_gap'])->toBeNull();
    });
});

describe('metrics:reps', function (): void {
    test('prints the cadency report used by the dashboard', function (): void {
        pastMeeting($this->institution, '2026-09-10 10:00:00', 2);

        expect(Artisan::call('metrics:reps'))->toBe(0)
            ->and(Artisan::output())->toContain('recorded_within_week', 'Current cadency', 'Comparison cadency');
    });
});

describe('metrics:reps:snapshot', function (): void {
    test('stores today\'s numbers over the last 30 days with the counts behind them', function (): void {
        pastMeeting($this->institution, '2026-09-10 10:00:00', 2);
        pastMeeting($this->institution, '2026-09-12 10:00:00', 20);
        pastMeeting($this->institution, '2026-08-01 10:00:00', 1);

        expect(Artisan::call('metrics:reps:snapshot'))->toBe(0);

        $snapshot = RepMetricSnapshot::query()->whereDate('taken_on', '2026-09-20')->where('scope_key', 'global')->where('definition_version', 2)->sole();

        expect($snapshot->window_days)->toBe(30)
            ->and($snapshot->is_baseline)->toBeFalse()
            ->and($snapshot->metrics['recorded_within_week'])->toBe(['value' => 100, 'part' => 1, 'whole' => 1])
            ->and($snapshot->metrics['active_reps'])->toBe(['value' => 50, 'part' => 1, 'whole' => 2]);
    });

    test('running it again the same day preserves that day\'s numbers', function (): void {
        Artisan::call('metrics:reps:snapshot');
        pastMeeting($this->institution, '2026-09-10 10:00:00', 2);
        Artisan::call('metrics:reps:snapshot');

        expect(RepMetricSnapshot::query()->whereDate('taken_on', '2026-09-20')->where('scope_key', 'global')->where('definition_version', 2)->sole()->value('recorded_within_week'))->toBeNull();
    });

    test('a back-filled day leaves out active reps, which keep no history', function (): void {
        pastMeeting($this->institution, '2026-08-20 10:00:00', 2);

        Artisan::call('metrics:reps:snapshot', ['--date' => '2026-09-01']);

        $snapshot = RepMetricSnapshot::query()->whereDate('taken_on', '2026-09-01')->where('scope_key', 'global')->where('definition_version', 2)->sole();

        expect($snapshot->metrics)->not->toHaveKey('active_reps')
            ->and($snapshot->value('recorded_within_week'))->toBe(100.0);
    });

    test('a future day is refused', function (): void {
        expect(Artisan::call('metrics:reps:snapshot', ['--date' => '2026-09-21']))->toBe(1)
            ->and(RepMetricSnapshot::query()->whereDate('taken_on', '2026-09-21')->exists())->toBeFalse();
    });

    test('runs every day on the scheduler', function (): void {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'metrics:reps:snapshot'));

        expect($event)->not->toBeNull()
            ->and($event->expression)->toBe('50 3 * * *');
    });
});
