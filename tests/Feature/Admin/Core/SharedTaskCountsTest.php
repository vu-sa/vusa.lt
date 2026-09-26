<?php

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = makeTenantUserWithRole('Communication Coordinator', Tenant::query()->first());
});

test('the shell badges get the pending and overdue task counts of the signed-in user', function (): void {
    $upcoming = Task::factory()->create(['due_date' => now()->addDays(3)]);
    $overdue = Task::factory()->create(['due_date' => now()->subDays(2)]);
    $completed = Task::factory()->completed()->create(['due_date' => now()->subDays(5)]);
    Task::factory()->create(['due_date' => now()->subDays(1)]); // assigned to nobody

    foreach ([$upcoming, $overdue, $completed] as $task) {
        $task->users()->attach($this->user->id);
    }

    asUser($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.tasks_count', 2)
            ->where('auth.user.overdue_tasks_count', 1)
        );
});
