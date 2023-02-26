<?php

namespace App\Services;

use App\Models\Doing;
use App\Models\Meeting;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TaskService
{
    // * Create tasks for users, when creating a doing with meeting type

    public static function storeTask(string $name, Model $model, Collection $users, ?string $due_date = null)
    {
        $task = Task::create([
            'name' => $name,
            'taskable_id' => $model->id,
            'taskable_type' => $model::class,
            'due_date' => $due_date,
        ]);

        $task->users()->sync($users->pluck('id'));

        return $task;
    }
}
