<?php

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
});

function taskWithAssignee(Meeting $meeting, ActionType $actionType, ?User $assignee = null): Task
{
    $task = Task::factory()->forMeeting($meeting)->create(['action_type' => $actionType]);
    $task->users()->attach(($assignee ?? User::factory()->create())->id);

    return $task->fresh();
}

describe('deleting a task', function (): void {
    test('a task with assignees can be deleted', function (): void {
        // task_user.task_id is a RESTRICT foreign key: without detaching first, this throws.
        $meeting = Meeting::factory()->hasAttached($this->institution)->create();
        $task = taskWithAssignee($meeting, ActionType::Manual);

        $task->delete();

        expect(Task::query()->whereKey($task->id)->exists())->toBeFalse()
            ->and(DB::table('task_user')->where('task_id', $task->id)->exists())->toBeFalse();
    });
});

describe('deleting the taskable', function (): void {
    test('soft deleting a meeting deletes its tasks', function (): void {
        $meeting = Meeting::factory()->hasAttached($this->institution)->create();
        $task = taskWithAssignee($meeting, ActionType::AgendaCompletion);

        $meeting->delete();

        expect(Meeting::withTrashed()->whereKey($meeting->id)->exists())->toBeTrue()
            ->and(Task::query()->whereKey($task->id)->exists())->toBeFalse();
    });

    test('force deleting a meeting deletes its tasks', function (): void {
        $meeting = Meeting::factory()->hasAttached($this->institution)->create();
        $task = taskWithAssignee($meeting, ActionType::AgendaCompletion);

        $meeting->forceDelete();

        expect(Task::query()->whereKey($task->id)->exists())->toBeFalse();
    });

    test('deleting an institution deletes its tasks', function (): void {
        $task = Task::factory()->create([
            'taskable_type' => 'institution',
            'taskable_id' => $this->institution->id,
            'action_type' => ActionType::PeriodicityGap,
        ]);
        $task->users()->attach(User::factory()->create()->id);

        $this->institution->delete();

        expect(Task::query()->whereKey($task->id)->exists())->toBeFalse();
    });
});

describe('tasks.destroy authorization', function (): void {
    test('a super admin can delete an automatic task', function (): void {
        // Some automatic tasks become unclosable — the meeting is gone, the agenda can no
        // longer be filled — and used to be stuck forever.
        $superAdmin = makeAdminUser($this->tenant);
        $meeting = Meeting::factory()->hasAttached($this->institution)->create();
        $task = taskWithAssignee($meeting, ActionType::AgendaCompletion, $superAdmin);

        asUser($superAdmin)->delete(route('tasks.destroy', $task->id))->assertRedirect();

        expect(Task::query()->whereKey($task->id)->exists())->toBeFalse();
    });

    test('a super admin can delete a manual task', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $meeting = Meeting::factory()->hasAttached($this->institution)->create();
        $task = taskWithAssignee($meeting, ActionType::Manual, $superAdmin);

        asUser($superAdmin)->delete(route('tasks.destroy', $task->id))->assertRedirect();

        expect(Task::query()->whereKey($task->id)->exists())->toBeFalse();
    });

    test('being assigned to a task is not enough to delete it', function (): void {
        $user = makeUser($this->tenant);
        $meeting = Meeting::factory()->hasAttached($this->institution)->create();
        $task = taskWithAssignee($meeting, ActionType::AgendaCompletion, $user);

        asUser($user)->delete(route('tasks.destroy', $task->id))->assertStatus(403);

        expect(Task::query()->whereKey($task->id)->exists())->toBeTrue();
    });
});
