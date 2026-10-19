<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class HandleTaskCreated implements ShouldQueue
{
    public function handle(TaskCreated $event): void
    {
        $task = $event->task;

        // A system task is announced by whatever created it (the meeting, the reservation status,
        // the approval request, "Ar vyko posėdis?"); its reminders follow the deadline.
        if ($task->action_type?->isAutoCompletable()) {
            return;
        }

        Notification::send($task->notifiableUsers(), new TaskAssignedNotification($task, $event->assigner));
    }
}
