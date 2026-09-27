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

describe('tasks.summary scope', function (): void {
    test('shows a padalinys reader the tasks held in their padalinys but not those held elsewhere', function (): void {
        Role::create(['name' => 'Meeting task reader', 'guard_name' => 'web'])
            ->givePermissionTo(['tasks.read.padalinys', 'meetings.read.padalinys']);
        $reader = makeTenantUserWithRole('Meeting task reader', $this->tenant);
        $otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
        $ownTask = summaryMeetingTaskFor(makeUser($this->tenant), $this->institution);
        // The meeting is readable; only the assignee's padalinys puts this task out of scope.
        $foreignTask = summaryMeetingTaskFor(makeUser($otherTenant), $this->institution);

        $response = asUser($reader)->get(route('tasks.summary'));

        $ids = collect($response->viewData('page')['props']['data'])->pluck('id');
        expect($ids)->toContain($ownTask->id)
            ->and($ids)->not->toContain($foreignTask->id);
    });

    test('hides a meeting task from a reader who may not open meetings in that padalinys', function (): void {
        Role::create(['name' => 'Task reader', 'guard_name' => 'web'])->givePermissionTo('tasks.read.padalinys');
        $reader = makeTenantUserWithRole('Task reader', $this->tenant);
        $meetingTask = summaryMeetingTaskFor(makeUser($this->tenant), $this->institution);
        $orphan = orphanTaskFor(makeUser($this->tenant));

        $response = asUser($reader)->get(route('tasks.summary'));

        $ids = collect($response->viewData('page')['props']['data'])->pluck('id');
        expect($ids)->toContain($orphan->id)
            ->and($ids)->not->toContain($meetingTask->id);
    });

    test('never lists a task nobody is assigned to, nor one about a user', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $unassigned = Task::factory()->create(['taskable_type' => 'institution', 'taskable_id' => $this->institution->id]);
        $aboutUser = summaryTaskFor($superAdmin, ['taskable_type' => 'user', 'taskable_id' => $superAdmin->id]);
        $listed = summaryMeetingTaskFor($superAdmin, $this->institution);

        $response = asUser($superAdmin)->get(route('tasks.summary'));

        $ids = collect($response->viewData('page')['props']['data'])->pluck('id');
        expect($ids)->toContain($listed->id)
            ->and($ids)->not->toContain($unassigned->id)
            ->and($ids)->not->toContain($aboutUser->id);
    });

    test('shows the central student representative coordinator the tasks of every padalinys', function (): void {
        $coordinator = makeTenantUserWithRole('Centrinio biuro studentų atstovų koordinatorius', $this->tenant);
        $otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
        $ownTask = summaryMeetingTaskFor(makeUser($this->tenant), $this->institution);
        $otherTask = summaryMeetingTaskFor(makeUser($otherTenant), Institution::factory()->for($otherTenant)->create());

        $response = asUser($coordinator)->get(route('tasks.summary'));

        $ids = collect($response->viewData('page')['props']['data'])->pluck('id');
        expect($ids)->toContain($ownTask->id)
            ->and($ids)->toContain($otherTask->id);
    });

    test('refuses a padalinys student representative coordinator, who only has their own tasks', function (): void {
        $coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);

        asUser($coordinator)->get(route('tasks.summary'))->assertForbidden();
    });
});

describe('tasks.summary filters', function (): void {
    test('narrows to the tasks assigned to the viewer', function (): void {
        Role::create(['name' => 'Task reader', 'guard_name' => 'web'])->givePermissionTo('tasks.read.padalinys');
        $reader = makeTenantUserWithRole('Task reader', $this->tenant);
        $mine = orphanTaskFor($reader);
        orphanTaskFor(makeUser($this->tenant));

        asUser($reader)
            ->getJson(route('api.v1.admin.tasks.index', ['scope' => 'tenant', 'assigned' => 'me']))
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $mine->id);
    });

    test('narrows to a chosen padalinys the viewer may read', function (): void {
        $coordinator = makeTenantUserWithRole('Centrinio biuro studentų atstovų koordinatorius', $this->tenant);
        $otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
        $ownTask = summaryMeetingTaskFor(makeUser($this->tenant), $this->institution);
        summaryMeetingTaskFor(makeUser($otherTenant), Institution::factory()->for($otherTenant)->create());

        asUser($coordinator)
            ->getJson(route('api.v1.admin.tasks.index', ['scope' => 'tenant', 'tenant' => [$this->tenant->id]]))
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $ownTask->id);
    });
});

describe('tasks.summary deletion offer', function (): void {
    test('lets the central student representative coordinator delete manual and automatic tasks alike', function (): void {
        $coordinator = makeTenantUserWithRole('Centrinio biuro studentų atstovų koordinatorius', $this->tenant);
        $assignee = makeUser($this->tenant);
        $manual = summaryMeetingTaskFor($assignee, $this->institution, ActionType::Manual);
        $automatic = summaryMeetingTaskFor($assignee, $this->institution, ActionType::AgendaCompletion);

        $response = asUser($coordinator)->get(route('tasks.summary'));

        $canDelete = collect($response->viewData('page')['props']['data'])->pluck('can_delete', 'id');
        expect($canDelete[$manual->id])->toBeTrue()
            ->and($canDelete[$automatic->id])->toBeTrue();
    });
});

describe('tasks.summary completion offer', function (): void {
    test('lets the assignee tick a task off but not a padalinys reader who may not update it', function (): void {
        Role::create(['name' => 'Task reader', 'guard_name' => 'web'])->givePermissionTo('tasks.read.padalinys');
        $reader = makeTenantUserWithRole('Task reader', $this->tenant);
        $assignee = makeUser($this->tenant);
        orphanTaskFor($assignee, ActionType::Manual);

        $readerView = asUser($reader)->get(route('tasks.summary'))->viewData('page')['props']['data'];
        $assigneeView = asUser($assignee)->get(route('userTasks'))->viewData('page')['props']['data'];

        expect($readerView[0]['can_update'])->toBeFalse()
            ->and($assigneeView[0]['can_update'])->toBeTrue();
    });
});
