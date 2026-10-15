<?php

use App\Models\Task;
use App\Models\Tenant;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
});

describe('tasks.updateCompletionStatus', function (): void {
    test('lets an assignee complete a manual task and reopen it', function (): void {
        $assignee = makeUser($this->tenant);
        $task = Task::factory()->withActionType(ActionType::Manual)->create();
        $task->users()->attach($assignee);

        asUser($assignee)
            ->post(route('tasks.updateCompletionStatus', $task), ['completed' => true])
            ->assertSessionHas('success');

        expect($task->fresh()->completed_at)->not->toBeNull();

        asUser($assignee)
            ->post(route('tasks.updateCompletionStatus', $task), ['completed' => false])
            ->assertSessionHas('success');

        expect($task->fresh()->completed_at)->toBeNull();
    });

    test('refuses to mark an automatic task by hand', function (): void {
        $assignee = makeUser($this->tenant);
        $task = Task::factory()->withActionType(ActionType::AgendaCompletion)->create();
        $task->users()->attach($assignee);

        asUser($assignee)
            ->post(route('tasks.updateCompletionStatus', $task), ['completed' => true])
            ->assertSessionHas('error', __('messages.task.automatic_not_markable'));

        expect($task->fresh()->completed_at)->toBeNull();
    });

    test('forbids someone who is neither assigned nor allowed to update tasks, with a 403', function (): void {
        $stranger = makeUser($this->tenant);
        $task = Task::factory()->withActionType(ActionType::Manual)->create();
        $task->users()->attach(makeUser($this->tenant));

        asUser($stranger)
            ->post(route('tasks.updateCompletionStatus', $task), ['completed' => true])
            ->assertForbidden();

        expect($task->fresh()->completed_at)->toBeNull();
    });

    test('lets the central student representative coordinator complete a manual task assigned to someone else', function (): void {
        $coordinator = makeTenantUserWithRole('Centrinio biuro studentų atstovų koordinatorius', $this->tenant);
        $task = Task::factory()->withActionType(ActionType::Manual)->create();
        $task->users()->attach(makeUser($this->tenant));

        asUser($coordinator)
            ->post(route('tasks.updateCompletionStatus', $task), ['completed' => true])
            ->assertSessionHas('success');

        expect($task->fresh()->completed_at)->not->toBeNull();
    });
});
