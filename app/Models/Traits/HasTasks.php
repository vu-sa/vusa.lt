<?php

namespace App\Models\Traits;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Support\Collection;

trait HasTasks {

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