<?php

namespace App\Listeners;

use App\Actions\ActivityRequests\SendInstitutionActivityRequests;
use App\Events\TaskCreated;
use App\Models\Institution;
use App\Notifications\TaskAssignedNotification;
use App\Tasks\Enums\ActionType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class HandleTaskCreated implements ShouldQueue
{
    public function __construct(private readonly SendInstitutionActivityRequests $activityRequests) {}

    public function handle(TaskCreated $event): void
    {
        $task = $event->task;

        // ApprovalRequestedNotification already asks the approvers; a task notice would repeat it.
        if ($task->action_type === ActionType::Approval) {
            return;
        }

        if ($task->action_type === ActionType::PeriodicityGap && $task->taskable instanceof Institution) {
            $this->activityRequests->forTask($task, $task->taskable, $task->notifiableUsers());

            return;
        }

        Notification::send($task->notifiableUsers(), new TaskAssignedNotification($task, $event->assigner));
    }
}
