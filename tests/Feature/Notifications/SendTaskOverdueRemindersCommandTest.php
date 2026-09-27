<?php

use App\Models\Task;
use App\Models\Tenant;
use App\Notifications\TaskOverdueNotification;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

pest()->use(RefreshDatabase::class);

describe('notifications:task-overdue-reminders', function (): void {
    test('sends each assignee one digest of their overdue tasks and nobody else anything', function (): void {
        Notification::fake();
        $tenant = Tenant::query()->first();
        $late = makeUser($tenant);
        $onTime = makeUser($tenant);
        Task::factory()->withActionType(ActionType::Manual)->create(['name' => 'Pateikti ataskaitą', 'due_date' => now()->subDays(2)])
            ->users()->attach($late);
        Task::factory()->withActionType(ActionType::Manual)->create(['due_date' => now()->subDay(), 'completed_at' => now()])
            ->users()->attach($onTime);
        Task::factory()->withActionType(ActionType::Manual)->create(['due_date' => now()->addDay()])
            ->users()->attach($onTime);

        $this->artisan('notifications:task-overdue-reminders')->assertSuccessful();

        Notification::assertSentToTimes($late, TaskOverdueNotification::class, 1);
        Notification::assertSentTo($late, TaskOverdueNotification::class, fn (TaskOverdueNotification $notification) => str_contains($notification->body($late), 'Pateikti ataskaitą'));
        Notification::assertNotSentTo($onTime, TaskOverdueNotification::class);
    });
});
