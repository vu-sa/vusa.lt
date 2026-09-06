<?php

namespace App\Tasks\Handlers;

use App\Models\Task;
use App\Models\User;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PickupTaskHandler extends BaseTaskHandler
{
    /**
     * @param  Collection<int, User>  $users
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
            actionType: ActionType::Pickup,
            totalItems: 1,
        );

        return $this->create($data);
    }

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

    public function findExistingTask(Model $model): ?Task
    {
        return Task::query()
            ->with('users')
            ->where('taskable_type', $model->getMorphClass())
            ->where('taskable_id', $model->getKey())
            ->where('action_type', ActionType::Pickup)
            ->whereNull('completed_at')
            ->first();
    }

    protected function incrementTotalItems(Task $task, ?string $dueDate): Task
    {
        $metadata = $task->metadata ?? ['items_total' => 0, 'items_completed' => 0];
        $metadata['items_total'] = ($metadata['items_total'] ?? 0) + 1;
        $task->metadata = $metadata;

        if ($dueDate) {
            $parsedDueDate = Carbon::parse($dueDate);
            if (! $task->due_date || $parsedDueDate->greaterThan($task->due_date)) {
                $task->due_date = $parsedDueDate;
            }
        }

        $task->save();

        return $task;
    }
}
