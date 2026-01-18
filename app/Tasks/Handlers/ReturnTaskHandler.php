<?php

namespace App\Tasks\Handlers;

use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Handles Return tasks with progress tracking for reservation resources.
 *
 * Return tasks are created when a reservation resource is lent and track
 * the progress of returning multiple resources. They auto-complete when all
 * resources have been returned (state changed to Returned).
 */
class ReturnTaskHandler extends BaseTaskHandler
{
    /**
     * Find or create a return task with progress tracking.
     *
     * If a task already exists for the model, increments the total items count.
     *
     * @param  Model  $model  The reservation model
     * @param  Collection<int, \App\Models\User>  $users  Users assigned to the task
     */
    public function findOrCreate(
        string $name,
        Model $model,
        Collection $users,
        ?string $dueDate = null
    ): Task {
        $existingTask = $this->findExistingTask($model);

        if ($existingTask) {
            return $this->incrementTotalItems($existingTask, $dueDate);
        }

        $data = CreateTaskData::withProgress(
            name: $name,
            taskable: $model,
            users: $users,
            dueDate: $dueDate,
            actionType: ActionType::Return,
            totalItems: 1,
        );

        return $this->create($data);
    }

    /**
     * Increment progress when a resource is returned.
     *
     * @return bool True if task was completed (all items returned)
     */
    public function incrementProgress(Task $task, string $resourceName): bool
    {
        $completed = $task->incrementProgress();

        if ($completed) {
            $this->notifyUsersOfCompletion(
                $task,
                __('All items have been processed')
            );
        }

        return $completed;
    }

    /**
     * Increment progress for a reservation's return task.
     *
     * @param  Model  $reservation
     */
    public function incrementProgressForModel($reservation, string $resourceName): bool
    {
        $task = $this->findExistingTask($reservation);

        if (! $task) {
            return false;
        }

        return $this->incrementProgress($task, $resourceName);
    }

    /**
     * Find an existing incomplete return task for the model.
     */
    public function findExistingTask(Model $model): ?Task
    {
        return Task::query()
            ->with('users')
            ->where('taskable_type', get_class($model))
            ->where('taskable_id', $model->getKey())
            ->where('action_type', ActionType::Return)
            ->whereNull('completed_at')
            ->first();
    }

    /**
     * Increment total items and update due date if needed.
     */
    protected function incrementTotalItems(Task $task, ?string $dueDate): Task
    {
        $metadata = $task->metadata ?? ['items_total' => 0, 'items_completed' => 0];
        $metadata['items_total'] = ($metadata['items_total'] ?? 0) + 1;
        $task->metadata = $metadata;

        // Update due date if provided date is later (return tasks should use latest end time)
        if ($dueDate && (! $task->due_date || $dueDate > $task->due_date)) {
            $task->due_date = $dueDate;
        }

        $task->save();

        return $task;
    }
}
