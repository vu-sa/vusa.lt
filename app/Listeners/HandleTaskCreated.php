<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Models\Institution;
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

        // ApprovalRequestedNotification already asks the approvers; a task notice would repeat it.
        if ($task->action_type === ActionType::Approval) {
            return;
        }

        $notification = $task->action_type === ActionType::PeriodicityGap
            && $task->taskable instanceof Institution
            ? new InstitutionActivityNotification($task, $task->taskable)
            : new TaskAssignedNotification($task, $event->assigner);

        Notification::send($task->notifiableUsers(), $notification);
    }
}
