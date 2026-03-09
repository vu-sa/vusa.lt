<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class HandleTaskCreated implements ShouldQueue
{
    public function handle(TaskCreated $event): void
    {
        $task = $event->task;

        // Check if task is instance of Task
        if (! $task instanceof Task) {
            return;
        }

        // Get the user who created the task (the assigner)
        $assigner = auth()->user();

        Notification::send($task->users, new TaskAssignedNotification($task, $assigner));
    }
}
