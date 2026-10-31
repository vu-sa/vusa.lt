<?php

use App\Models\Task;
use App\Models\Tenant;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskReminderNotification;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

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

test('overdue summaries skip unchanged lists until four weeks have passed', function (int $daysAgo, bool $sends): void {
    Notification::fake();
    $user = makeUser(Tenant::query()->first());
    Task::factory()->withActionType(ActionType::Manual)->create([
        'due_date' => now()->subDays(40), 'created_at' => now()->subDays(50),
    ])->users()->attach($user);
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskOverdueNotification::class,
        'data' => [],
        'created_at' => now()->subDays($daysAgo),
        'updated_at' => now()->subDays($daysAgo),
    ]);

    $this->artisan('notifications:task-overdue-reminders')->assertSuccessful();

    if ($sends) {
        Notification::assertSentToTimes($user, TaskOverdueNotification::class, 1);
    } else {
        Notification::assertNothingSent();
    }
})->with(['recent notice' => [7, false], 'four weeks' => [28, true]]);

test('new overdue tasks bypass the cooldown and include the full list', function (bool $backfilled): void {
    Notification::fake();
    $user = makeUser(Tenant::query()->first());
    Task::factory()->withActionType(ActionType::Manual)->create([
        'name' => 'Old unfinished task', 'due_date' => now()->subDays(40), 'created_at' => now()->subDays(50),
    ])->users()->attach($user);
    Task::factory()->withActionType(ActionType::Manual)->create([
        'name' => 'New overdue task',
        'due_date' => $backfilled ? now()->subDays(40) : now()->subDays(2),
        'created_at' => $backfilled ? now()->subDay() : now()->subDays(50),
    ])->users()->attach($user);
    $user->notifications()->create([
        'id' => (string) Str::uuid(), 'type' => TaskOverdueNotification::class,
        'data' => [], 'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7),
    ]);

    $this->artisan('notifications:task-overdue-reminders')->assertSuccessful();

    Notification::assertSentToTimes($user, TaskOverdueNotification::class, 1);
    Notification::assertSentTo($user, TaskOverdueNotification::class, fn ($notification): bool => str_contains($notification->body($user), 'Old unfinished task') && str_contains($notification->body($user), 'New overdue task')
    );
})->with(['new deadline' => false, 'backfilled late task' => true]);

test('default overdue delivery queues a digest through the real channel manager', function (): void {
    config(['queue.default' => 'sync']);
    Notification::swap(app(ChannelManager::class));
    $user = makeUser(Tenant::query()->first());
    Task::factory()->withActionType(ActionType::Manual)->create(['due_date' => now()->subDay()])->users()->attach($user);

    $this->artisan('notifications:task-overdue-reminders')->assertSuccessful();

    $this->assertDatabaseHas('notification_digest_queue', [
        'user_id' => $user->id, 'notification_class' => TaskOverdueNotification::class, 'category' => 'task',
    ]);
    expect($user->notifications()->where('type', TaskOverdueNotification::class)->exists())->toBeTrue();
});

test('another notification type does not suppress an overdue summary', function (): void {
    Notification::fake();
    $user = makeUser(Tenant::query()->first());
    Task::factory()->withActionType(ActionType::Manual)->create([
        'due_date' => now()->subDays(40), 'created_at' => now()->subDays(50),
    ])->users()->attach($user);
    $user->notifications()->create([
        'id' => (string) Str::uuid(), 'type' => TaskReminderNotification::class,
        'data' => [], 'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
    ]);

    $this->artisan('notifications:task-overdue-reminders')->assertSuccessful();

    Notification::assertSentToTimes($user, TaskOverdueNotification::class, 1);
});
