<?php

use App\Actions\ResolveTaskAssignees;
use App\Actions\Schedulable\TaskNotifier;
use App\Enums\ResponsibilityPurpose;
use App\Events\TaskCreated;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskAutoCompletedNotification;
use App\Notifications\TaskReminderNotification;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\ApprovalTaskHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

pest()->use(RefreshDatabase::class);

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
function attachDuty(Institution $institution, User $user, ?string $start, ?string $end): Duty
{
    $duty = Duty::factory()->for($institution)->create();

    $user->duties()->attach($duty, [
        'start_date' => $start,
        'end_date' => $end,
    ]);

    return $duty;
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
    test('a future holder carries a future meeting task but receives no notification before the term starts', function (): void {
        $futureHolder = User::factory()->create();
        $duty = attachDuty($this->institution, $futureHolder, now()->addMonth()->toDateString(), null);
        grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);

        $meetingDate = now()->addMonths(2)->toDateTimeString();
        $task = meetingTaskFor($this->institution, [$futureHolder], $meetingDate);

        expect(ResolveTaskAssignees::forMeeting($task->taskable)->pluck('id')->all())->toBe([$futureHolder->id])
            ->and($task->users->pluck('id')->all())->toBe([$futureHolder->id])
            ->and($task->notifiableUsers())->toBeEmpty();
    });

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

    test('keeps a secretary nominated for the term the meeting falls in', function (): void {
        $secretary = User::factory()->create();

        $cadence = Cadence::factory()->create([
            'start_date' => now()->subYears(2),
            'end_date' => now()->addMonths(2),
        ]);

        $this->institution->secretaries()->attach($secretary, ['cadence_id' => $cadence->id]);

        $task = meetingTaskFor($this->institution, [$secretary]);

        expect($task->notifiableUsers()->pluck('id')->all())->toBe([$secretary->id]);
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

    test('a periodicity task gets no day-count reminder, its activity question already asks', function (): void {
        $current = User::factory()->create();
        attachDuty($this->institution, $current, now()->subYears(3)->toDateString(), null);

        $task = Task::factory()->create([
            'taskable_type' => MorphMap::alias(Institution::class),
            'taskable_id' => $this->institution->id,
            'action_type' => ActionType::PeriodicityGap,
            'due_date' => now()->addDays(3),
        ]);
        $task->users()->sync([$current->id]);

        TaskNotifier::notifyDaysLeft(3);

        Notification::assertNotSentTo($current, TaskReminderNotification::class);
    });
});

describe('task reminder intervals', function (): void {
    test('a reminder skips users who deselected that interval', function (): void {
        $wantsIt = User::factory()->create();
        $optedOut = User::factory()->create(['notification_preferences' => ['reminder_settings' => ['task_reminder_days' => [1]]]]);

        $task = Task::factory()->create(['taskable_type' => 'user', 'taskable_id' => $wantsIt->id, 'due_date' => now()->addDays(3)]);
        $task->users()->sync([$wantsIt->id, $optedOut->id]);

        TaskNotifier::notifyDaysLeft(3);

        Notification::assertSentTo($wantsIt, TaskReminderNotification::class);
        Notification::assertNotSentTo($optedOut, TaskReminderNotification::class);
    });
});

describe('manual tasks', function (): void {
    test('dispatches TaskCreated once per saved task after commit', function (bool $separateTasks, int $expectedCount): void {
        $admin = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->institution->tenant);
        $assignees = User::factory()->count(2)->create();
        $taskableType = MorphMap::alias(User::class);
        Event::fake([TaskCreated::class]);

        DB::transaction(function () use ($admin, $assignees, $taskableType, $separateTasks): void {
            asUser($admin)->post(route('tasks.store'), [
                'name' => 'Sutvarkyti dokumentus',
                'taskable_type' => $taskableType,
                'taskable_id' => $admin->id,
                'due_date' => now()->addWeek()->getTimestampMs(),
                'responsible_people' => $assignees->modelKeys(),
                'separate_tasks' => $separateTasks,
            ])->assertRedirect()->assertSessionHasNoErrors();

            Event::assertNotDispatched(TaskCreated::class);
        });

        $taskIds = Task::query()->where('taskable_type', $taskableType)->where('taskable_id', $admin->id)->pluck('id')->all();
        $dispatchedTaskIds = Event::dispatched(TaskCreated::class)
            ->map(fn (array $arguments) => $arguments[0]->task->id)
            ->all();

        expect($taskIds)->toHaveCount($expectedCount);
        expect($dispatchedTaskIds)->toEqualCanonicalizing($taskIds);
    })->with([
        'one shared task' => [false, 1],
        'one task per assignee' => [true, 2],
    ]);

    test('creating a task by hand tells its assignees, and names who assigned it', function (): void {
        $admin = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->institution->tenant);
        $assignee = User::factory()->create();

        asUser($admin)->post(route('tasks.store'), [
            'name' => 'Sutvarkyti dokumentus',
            'taskable_type' => MorphMap::alias(User::class),
            'taskable_id' => $admin->id,
            'due_date' => now()->addWeek()->getTimestampMs(),
            'responsible_people' => [$assignee->id],
            'separate_tasks' => false,
        ])->assertRedirect();

        Notification::assertSentTo($assignee, TaskAssignedNotification::class, fn (TaskAssignedNotification $notification): bool => str_contains($notification->body($assignee), $admin->name));
    });
});

describe('automatic completion', function (): void {
    test('the person whose action completed the task is not told about it', function (): void {
        $completer = User::factory()->create();
        $other = User::factory()->create();

        $task = Task::factory()->create(['taskable_type' => 'user', 'taskable_id' => $completer->id]);
        $task->users()->sync([$completer->id, $other->id]);

        app(ApprovalTaskHandler::class)->complete($task->fresh(), 'Patvirtinta', $completer);

        Notification::assertSentTo($other, TaskAutoCompletedNotification::class);
        Notification::assertNotSentTo($completer, TaskAutoCompletedNotification::class);
    });
});
