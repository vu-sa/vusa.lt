<?php

namespace App\Models\Traits;

use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait HasTasks
{
    /**
     * A task exists to get something done about this record, so it goes when the record does —
     * including on a soft delete, where an orphaned task would otherwise keep nagging assignees
     * about something they can no longer open. Restoring the record does not bring the tasks
     * back; `tasks:repopulate` recreates the automatic ones.
     */
    protected static function bootHasTasks(): void
    {
        static::deleting(function (Model $model): void {
            // Queried rather than read off the relation so the closure stays typed to Model,
            // and iterated rather than mass-deleted: Task detaches its assignees in its own
            // `deleting` hook, and task_user.task_id is a RESTRICT foreign key.
            Task::query()
                ->where('taskable_type', $model->getMorphClass())
                ->where('taskable_id', $model->getKey())
                ->get()
                ->each
                ->delete();
        });
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function storeTask(string $name, Collection $users, ?string $due_date = null)
    {
        $task = TaskService::storeTask($name, $this, $users, $due_date);

        return $task;
    }

    public function storeTasks(array $tasksWithDates, Collection $users)
    {
        $tasks = [];

        foreach ($tasksWithDates as $taskWithDate) {
            $tasks[] = $this->storeTask($taskWithDate['name'], $users, $taskWithDate['due_date']);
        }

        return $tasks;
    }
}
