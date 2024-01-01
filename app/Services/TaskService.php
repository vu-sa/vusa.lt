<?php

namespace App\Services;

use App\Events\TaskCreated;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public static function storeTask(string $name, Model $model, Collection $users, ?string $due_date = null)
    {
        $task = new Task();

        $task = $task->fill([
            'name' => $name,
            'taskable_id' => $model->id,
            'taskable_type' => $model::class,
            'due_date' => $due_date,
        ]);

        return DB::transaction(function () use ($task, $users) {

            $task->save();

            $task->users()->sync($users->pluck('id'));

            event(new TaskCreated($task));

            return $task;
        });
    }
}
