<?php

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('progress increments from stale instances and completes at the total', function (): void {
    $this->freezeTime();
    $task = Task::factory()->withProgress(0, 3)->create();
    $stale = Task::query()->findOrFail($task->id);

    expect($task->incrementProgress())->toBeFalse();
    expect($stale->incrementProgress(2))->toBeTrue();

    $task->refresh();
    expect($task->metadata['items_completed'])->toBe(3)
        ->and($task->completed_at->equalTo(now()))->toBeTrue();
});
