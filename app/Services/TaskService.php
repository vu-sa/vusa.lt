<?php

namespace App\Services;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * @param  Model&object{id: int|string}  $model
     * @param  ActionType|string|null  $actionType  Optional action type for auto-completion
     * @param  array|null  $metadata  Optional metadata for progress tracking
     */
    public static function storeTask(
        string $name,
        Model $model,
        Collection $users,
        ?string $due_date = null,
        ActionType|string|null $actionType = null,
        ?array $metadata = null
    ) {
        $task = new Task;

        $task = $task->fill([
            'name' => $name,
            'taskable_id' => $model->id,
            'taskable_type' => $model::class,
            'due_date' => $due_date,
            'action_type' => $actionType,
            'metadata' => $metadata,
        ]);

        DB::transaction(function () use ($task, $users) {
            $task->save();
            $task->users()->sync($users->pluck('id'));
        });

        $task->refresh();

        event(new TaskCreated($task));

        return $task;
    }

    /**
     * Find or create a task with progress tracking for a reservation.
     * If task exists, updates the total items count.
     *
     * @param  Model&object{id: int|string}  $model
     */
    public static function findOrCreateProgressTask(
        string $name,
        Model $model,
        Collection $users,
        ?string $due_date,
        ActionType $actionType,
        int $totalItems = 1
    ): Task {
        // Try to find existing incomplete task with same action type for this model
        $existingTask = Task::query()
            ->where('taskable_type', $model::class)
            ->where('taskable_id', $model->id)
            ->where('action_type', $actionType)
            ->whereNull('completed_at')
            ->first();

        if ($existingTask) {
            // Update total items count in metadata
            $metadata = $existingTask->metadata ?? ['items_total' => 0, 'items_completed' => 0];
            $metadata['items_total'] = ($metadata['items_total'] ?? 0) + 1;
            $existingTask->metadata = $metadata;

            // Update due date if provided date is later
            if ($due_date && (! $existingTask->due_date || $due_date > $existingTask->due_date)) {
                $existingTask->due_date = $due_date;
            }

            $existingTask->save();

            return $existingTask;
        }

        // Create new task with progress metadata
        return self::storeTask(
            $name,
            $model,
            $users,
            $due_date,
            $actionType,
            [
                'items_total' => $totalItems,
                'items_completed' => 0,
            ]
        );
    }
}
