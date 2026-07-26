<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Models\Institution;
use App\Models\Task;
use App\Notifications\InstitutionActivityNotification;
use App\Notifications\TaskAssignedNotification;
use App\Tasks\Enums\ActionType;
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

        $notification = $task->action_type === ActionType::PeriodicityGap
            && $task->taskable instanceof Institution
            ? new InstitutionActivityNotification($task, $task->taskable)
            : new TaskAssignedNotification($task, $assigner);

        Notification::send($task->users, $notification);
    }
}
