<?php

namespace App\Tasks\Handlers;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAutoCompletedNotification;
use App\Tasks\DTOs\CreateTaskData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

abstract class BaseTaskHandler implements TaskHandler
{
    public function create(CreateTaskData $data): Task
    {
        $task = new Task;

        $task->fill([
            'name' => $data->name,
            'description' => $data->description,
            'taskable_id' => $data->taskable->getKey(),
            'taskable_type' => $data->taskable->getMorphClass(),
            'due_date' => $data->dueDate,
            'action_type' => $data->actionType,
            'metadata' => $data->metadata,
        ]);

        DB::transaction(function () use ($task, $data): void {
            $task->save();
            $task->users()->sync($data->users->pluck('id'));
        });

        $task->refresh();

        event(new TaskCreated($task, auth()->user() instanceof User ? auth()->user() : null));

        return $task;
    }

    /**
     * @param  Task  $task
     * @param  User|null  $completedBy  Excluded from completion notifications.
     */
    public function complete($task, ?string $reason = null, ?User $completedBy = null): void
    {
        $task->completed_at = now();
        $task->save();

        if ($reason) {
            $this->notifyUsersOfCompletion($task, $reason, $completedBy);
        }
    }

    /**
     * @param  User|null  $completedBy  Excluded from completion notifications.
     */
    protected function notifyUsersOfCompletion(Task $task, string $reason, ?User $completedBy = null): void
    {
        $recipients = $task->notifiableUsers()->reject(fn (User $user): bool => $user->is($completedBy));

        Notification::send($recipients, new TaskAutoCompletedNotification($task, $reason, $completedBy));
    }
}
