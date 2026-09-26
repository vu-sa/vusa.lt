<?php

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
});

/**
 * The summary reaches a task through its assignees' tenants, so every fixture needs one.
 */
function summaryTaskFor(User $assignee, array $attributes = []): Task
{
    $task = Task::factory()->create($attributes);
    $task->users()->attach($assignee->id);

    return $task->fresh();
}

/** Leaves the row behind with a taskable_id that resolves to nothing. */
function orphanTaskFor(User $assignee, ActionType $actionType = ActionType::PeriodicityGap): Task
{
    return summaryTaskFor($assignee, [
        'taskable_type' => MorphMap::alias(Institution::class),
        'taskable_id' => 'deleted-institution-id',
        'action_type' => $actionType,
    ]);
}

/** A meeting-taskable task (agenda creation/completion), the counterpart to an institution one. */
function summaryMeetingTaskFor(User $assignee, Institution $institution, ActionType $actionType = ActionType::AgendaCompletion): Task
{
    $meeting = Meeting::factory()->hasAttached($institution)->create();

    return summaryTaskFor($assignee, [
        'taskable_type' => MorphMap::alias(Meeting::class),
        'taskable_id' => $meeting->id,
        'action_type' => $actionType,
    ]);
}

describe('tasks.summary listing', function (): void {
    test('lists a task whose subject was deleted underneath it', function (): void {
        // Before this, an orphan matched none of the compound-authorization branches, so the
        // only listing spanning every tenant could never show — or clear — it.
        $superAdmin = makeAdminUser($this->tenant);
        $orphan = orphanTaskFor($superAdmin);

        $response = asUser($superAdmin)->get(route('tasks.summary'));

        $response->assertOk();
        expect(collect($response->viewData('page')['props']['data'])->pluck('id'))
            ->toContain($orphan->id);
    });

    test('marks an orphaned automatic task deletable for a super admin', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        orphanTaskFor($superAdmin);

        $response = asUser($superAdmin)->get(route('tasks.summary'));

        expect($response->viewData('page')['props']['data'][0]['can_delete'])->toBeTrue();
    });

    test('does not offer deletion to a user who merely holds the task', function (): void {
        // tasks.delete is seeded for no role, so offering the action in the table only ever
        // produced a 403 on click.
        Role::create(['name' => 'Task reader', 'guard_name' => 'web'])->givePermissionTo('tasks.read.padalinys');
        $manager = makeTenantUserWithRole('Task reader', $this->tenant);
        orphanTaskFor($manager, ActionType::Manual);

        $response = asUser($manager)->get(route('tasks.summary'));

        $tasks = $response->viewData('page')['props']['data'];
        expect($tasks)->not->toBeEmpty()
            ->and($tasks[0]['can_delete'])->toBeFalse();
    });
});

describe('tasks.summary completion filter', function (): void {
    test('hides completed tasks unless they are asked for', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $done = orphanTaskFor($superAdmin);
        $done->update(['completed_at' => now()]);

        $response = asUser($superAdmin)->get(route('tasks.summary'));

        expect($response->viewData('page')['props']['data'])->toBeEmpty();
    });

    test('shows both when the filter is set to all', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $done = orphanTaskFor($superAdmin);
        $done->update(['completed_at' => now()]);
        orphanTaskFor($superAdmin);

        $response = asUser($superAdmin)->get(route('tasks.summary', ['completion' => 'all']));

        expect($response->viewData('page')['props']['data'])->toHaveCount(2);
    });
});

describe('tasks.summary taskable_type filter', function (): void {
    test('narrows to institution tasks without hiding a meeting task from the same list', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $institutionTask = orphanTaskFor($superAdmin);
        $meetingTask = summaryMeetingTaskFor($superAdmin, $this->institution);

        $response = asUser($superAdmin)->get(route('tasks.summary', ['taskable_type' => ['institution']]));

        $ids = collect($response->viewData('page')['props']['data'])->pluck('id');
        expect($ids)->toContain($institutionTask->id)
            ->and($ids)->not->toContain($meetingTask->id);
    });

    test('a caller wanting both institution and meeting tasks gets both at once', function (): void {
        // This is the "view meeting tasks" link from ShowAtstovavimas: a periodicity-gap task
        // (taskable=institution) is exactly as much "about a meeting" as an agenda task
        // (taskable=meeting), so asking for meeting-related work should surface both.
        $superAdmin = makeAdminUser($this->tenant);
        $institutionTask = orphanTaskFor($superAdmin);
        $meetingTask = summaryMeetingTaskFor($superAdmin, $this->institution);

        $response = asUser($superAdmin)->get(route('tasks.summary', ['taskable_type' => ['institution', 'meeting']]));

        $ids = collect($response->viewData('page')['props']['data'])->pluck('id');
        expect($ids)->toContain($institutionTask->id)
            ->and($ids)->toContain($meetingTask->id);
    });

    test('accepts a single hand-typed value the same as an array', function (): void {
        // A bookmarked or hand-typed URL carries `?taskable_type=meeting`, not
        // `taskable_type[]=meeting` — IndexTasksRequest::prepareForValidation() normalizes it.
        $superAdmin = makeAdminUser($this->tenant);
        $meetingTask = summaryMeetingTaskFor($superAdmin, $this->institution);
        orphanTaskFor($superAdmin);

        $response = asUser($superAdmin)->get(route('tasks.summary').'?taskable_type=meeting');

        $tasks = $response->viewData('page')['props']['data'];
        expect(collect($tasks)->pluck('id'))->toContain($meetingTask->id)
            ->and($tasks)->toHaveCount(1);
    });

    test('counts pending, overdue and assigned tasks for the chips regardless of the active filter', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        orphanTaskFor($superAdmin)->update(['due_date' => now()->subDay()]);
        summaryMeetingTaskFor($superAdmin, $this->institution);
        orphanTaskFor($superAdmin)->update(['completed_at' => now()]);

        $response = asUser($superAdmin)->get(route('tasks.summary', ['taskable_type' => ['meeting']]));

        expect($response->viewData('page')['props']['taskCounts'])->toMatchArray([
            'pending' => 2,
            'overdue' => 1,
            'assigned' => 2,
            'completed' => 1,
        ]);
    });
});

describe('tasks.destroy for an orphaned task', function (): void {
    test('a super admin can delete an orphaned automatic task', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $orphan = orphanTaskFor($superAdmin);

        asUser($superAdmin)->delete(route('tasks.destroy', $orphan->id))->assertRedirect();

        expect(Task::query()->whereKey($orphan->id)->exists())->toBeFalse();
    });
});
