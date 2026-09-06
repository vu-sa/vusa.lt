<?php

namespace App\Tasks\Handlers;

use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;

class ApprovalTaskHandler extends BaseTaskHandler
{
    #[\Override]
    public function create(CreateTaskData $data): Task
    {
        $data = new CreateTaskData(
            name: $data->name,
            taskable: $data->taskable,
            users: $data->users,
            dueDate: $data->dueDate,
            actionType: ActionType::Approval,
            metadata: $data->metadata,
        );

        return parent::create($data);
    }

    /**
     * @param  Model  $model
     */
    public function completeForModel($model, string $reason): void
    {
        $tasks = Task::query()
            ->with('users')
            ->where('action_type', ActionType::Approval)
            ->whereNull('completed_at')
            ->whereMorphedTo('taskable', $model)
            ->get();

        foreach ($tasks as $task) {
            $this->complete($task, $reason);
        }
    }
}
