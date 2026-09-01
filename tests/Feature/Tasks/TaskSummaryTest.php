<?php

use App\Models\Institution;
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

describe('tasks.summary listing', function (): void {
    test('lists a task whose subject was deleted underneath it', function (): void {
        // Before this, an orphan matched none of the compound-authorization branches, so the
        // only listing spanning every tenant could never show — or clear — it.
        $superAdmin = makeAdminUser($this->tenant);
        $orphan = orphanTaskFor($superAdmin);

        $response = asUser($superAdmin)->get(route('tasks.summary'));

        $response->assertOk();
        expect(collect($response->viewData('page')['props']['tasks']['data'])->pluck('id'))
            ->toContain($orphan->id);
    });

    test('marks an orphaned automatic task deletable for a super admin', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        orphanTaskFor($superAdmin);

        $response = asUser($superAdmin)->get(route('tasks.summary'));

        expect($response->viewData('page')['props']['tasks']['data'][0]['can_delete'])->toBeTrue();
    });

    test('does not offer deletion to a user who merely holds the task', function (): void {
        // tasks.delete is seeded for no role, so offering the action in the table only ever
        // produced a 403 on click.
        $manager = makeTenantUserWithRole('Resource Manager', $this->tenant);
        orphanTaskFor($manager, ActionType::Manual);

        $response = asUser($manager)->get(route('tasks.summary'));

        $tasks = $response->viewData('page')['props']['tasks']['data'];
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

        expect($response->viewData('page')['props']['tasks']['data'])->toBeEmpty()
            ->and($response->viewData('page')['props']['filters']['completion'])->toBe('pending');
    });

    test('shows both when the filter is set to all', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $done = orphanTaskFor($superAdmin);
        $done->update(['completed_at' => now()]);
        orphanTaskFor($superAdmin);

        $response = asUser($superAdmin)->get(route('tasks.summary', ['completion' => 'all']));

        expect($response->viewData('page')['props']['tasks']['data'])->toHaveCount(2);
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
