<?php

use App\Actions\Schedulable\TaskNotifier;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    config(['queue.default' => 'sync']);

    $this->institution = Institution::factory()
        ->for(Tenant::query()->first() ?? Tenant::factory()->create())
        ->create();
});

/**
 * Attach a user to the institution for the given term. A null end date means still serving.
 */
function attachDuty(Institution $institution, User $user, ?string $start, ?string $end): void
{
    $duty = Duty::factory()->for($institution)->create();

    $user->duties()->attach($duty, [
        'start_date' => $start,
        'end_date' => $end,
    ]);
}

function meetingTaskFor(Institution $institution, array $users, ?string $meetingDate = null): Task
{
    $meeting = Meeting::factory()
        ->hasAttached($institution)
        ->create(['start_time' => $meetingDate ?? now()->subYear()->toDateTimeString()]);

    $task = Task::factory()->forMeeting($meeting)->create(['action_type' => ActionType::AgendaCompletion]);
    $task->users()->sync(collect($users)->pluck('id'));

    return $task->fresh();
}

describe('ResolveTaskAudience', function (): void {
    test('drops an assignee whose duty in the institution has ended', function (): void {
        $former = User::factory()->create();
        attachDuty($this->institution, $former, now()->subYears(3)->toDateString(), now()->subYear()->toDateString());

        $task = meetingTaskFor($this->institution, [$former]);

        expect($task->notifiableUsers())->toBeEmpty()
            ->and($task->users)->toHaveCount(1);
    });

    test('keeps an assignee whose term covers both the meeting and today', function (): void {
        $current = User::factory()->create();
        attachDuty($this->institution, $current, now()->subYears(3)->toDateString(), null);

        $task = meetingTaskFor($this->institution, [$current]);

        expect($task->notifiableUsers()->pluck('id')->all())->toBe([$current->id]);
    });

    test('drops a current member who was not yet serving at the meeting', function (): void {
        // Backfilling an old sitting must not nag whoever holds the seat now.
        $newcomer = User::factory()->create();
        attachDuty($this->institution, $newcomer, now()->subMonth()->toDateString(), null);

        $task = meetingTaskFor($this->institution, [$newcomer], now()->subYear()->toDateTimeString());

        expect($task->notifiableUsers())->toBeEmpty();
    });

    test('an active duty in another institution does not keep a former member on the list', function (): void {
        $former = User::factory()->create();
        attachDuty($this->institution, $former, now()->subYears(3)->toDateString(), now()->subYear()->toDateString());
        attachDuty(Institution::factory()->for($this->institution->tenant)->create(), $former, now()->subMonth()->toDateString(), null);

        $task = meetingTaskFor($this->institution, [$former]);

        expect($task->notifiableUsers())->toBeEmpty();
    });

    test('keeps an administrator nominated for the term the meeting falls in', function (): void {
        $administrator = User::factory()->create();

        $cadence = Cadence::factory()->create([
            'start_date' => now()->subYears(2),
            'end_date' => now()->addMonths(2),
        ]);

        $this->institution->administrators()->attach($administrator, ['cadence_id' => $cadence->id]);

        $task = meetingTaskFor($this->institution, [$administrator]);

        expect($task->notifiableUsers()->pluck('id')->all())->toBe([$administrator->id]);
    });

    test('a manual task keeps assignees a person picked by hand', function (): void {
        $outsider = User::factory()->create();

        $meeting = Meeting::factory()->hasAttached($this->institution)->create(['start_time' => now()->subYear()]);
        $task = Task::factory()->forMeeting($meeting)->create(['action_type' => ActionType::Manual]);
        $task->users()->sync([$outsider->id]);

        expect($task->fresh()->notifiableUsers()->pluck('id')->all())->toBe([$outsider->id]);
    });

    test('a task with no institution behind it keeps all of its assignees', function (): void {
        $user = User::factory()->create();

        $task = Task::factory()->create(['taskable_type' => 'user', 'taskable_id' => $user->id]);
        $task->users()->sync([$user->id]);

        expect($task->fresh()->notifiableUsers()->pluck('id')->all())->toBe([$user->id]);
    });
});

describe('task reminders', function (): void {
    test('a reminder skips assignees who have left the institution', function (): void {
        $former = User::factory()->create();
        attachDuty($this->institution, $former, now()->subYears(3)->toDateString(), now()->subYear()->toDateString());

        $current = User::factory()->create();
        attachDuty($this->institution, $current, now()->subYears(3)->toDateString(), null);

        $task = meetingTaskFor($this->institution, [$former, $current], now()->subMonths(18)->toDateTimeString());
        $task->update(['due_date' => now()->addDays(3)]);

        TaskNotifier::notifyDaysLeft(3);

        Notification::assertSentTo($current, TaskReminderNotification::class);
        Notification::assertNotSentTo($former, TaskReminderNotification::class);
    });
});
