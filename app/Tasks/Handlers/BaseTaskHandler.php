<?php

namespace App\Tasks\Handlers;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Notifications\TaskAutoCompletedNotification;
use App\Tasks\DTOs\CreateTaskData;
use Illuminate\Support\Facades\DB;

/**
 * Base class for task handlers providing common functionality.
 */
abstract class BaseTaskHandler implements TaskHandler
{
    /**
     * Create a task using the provided data.
     */
    public function create(CreateTaskData $data): Task
    {
        $task = new Task;

        $task->fill([
            'name' => $data->name,
            'description' => $data->description,
            'taskable_id' => $data->taskable->getKey(),
            'taskable_type' => get_class($data->taskable),
            'due_date' => $data->dueDate,
            'action_type' => $data->actionType,
            'metadata' => $data->metadata,
        ]);

        DB::transaction(function () use ($task, $data) {
            $task->save();
            $task->users()->sync($data->users->pluck('id'));
        });

        $task->refresh();

        event(new TaskCreated($task));

        return $task;
    }

    /**
     * Complete a task and notify assigned users.
     */
    public function complete($task, ?string $reason = null): void
    {
        $task->completed_at = now();
        $task->save();

        if ($reason) {
            $this->notifyUsersOfCompletion($task, $reason);
        }
    }

    /**
     * Notify all assigned users that the task was auto-completed.
     */
    protected function notifyUsersOfCompletion(Task $task, string $reason): void
    {
        foreach ($task->users as $user) {
            $user->notify(new TaskAutoCompletedNotification($task, $reason));
        }
    }
}
