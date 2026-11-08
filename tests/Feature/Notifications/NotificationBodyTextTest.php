<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('notification channels preserve a task name as text', function (): void {
    $this->freezeTime();
    app()->setLocale('lt');
    $name = 'A <strong>B</strong> & C';
    $recipient = User::factory()->make();
    $notification = new TaskOverdueNotification(collect([Task::factory()->make(['name' => $name])]));

    expect($notification->body($recipient))->toBe($name)
        ->and($notification->toArray($recipient)['body'])->toBe($name)
        ->and($notification->digestLine($recipient))->toBe($name)
        ->and($notification->toDigestItem($recipient)['body'])->toBe($name);
    $push = $notification->toWebPush($recipient, null)->toArray();
    expect($push['body'])->toBe($name);
});

test('digest summaries preserve the assigning person name as text', function (): void {
    app()->setLocale('lt');
    $name = 'A <strong>B</strong> & C';
    $recipient = User::factory()->make();
    $notification = new TaskAssignedNotification(
        Task::factory()->make(['name' => 'Ataskaita']),
        User::factory()->make(['name' => $name]),
    );

    expect($notification->digestLine($recipient))->toContain($name);
});
