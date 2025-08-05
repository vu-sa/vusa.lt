<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Notifications\TaskCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class HandleTaskCreated implements ShouldQueue
{
    public function handle(TaskCreated $event)
    {
        $task = $event->task;

        // check if task is instance of Task
        if (! $task instanceof Task) {
            return;
        }

        Notification::send($task->users, new TaskCreatedNotification($task));
    }
}
