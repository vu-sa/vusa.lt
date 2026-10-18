<?php

namespace App\Listeners;

use App\Events\TaskCreated;
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

        // Its "Ar vyko posėdis?" is the notice, sent by tasks:repopulate once the institution is overdue.
        if ($task->action_type === ActionType::PeriodicityGap) {
            return;
        }

        Notification::send($task->notifiableUsers(), new TaskAssignedNotification($task, $event->assigner));
    }
}
