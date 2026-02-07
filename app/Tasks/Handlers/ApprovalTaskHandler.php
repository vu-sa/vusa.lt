<?php

namespace App\Tasks\Handlers;

use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Illuminate\Support\Str;

/**
 * Handles Approval tasks that auto-complete when an approval decision is made.
 *
 * Approval tasks are created when an approvable item enters the approval flow
 * and are automatically completed when the approver makes a decision.
 */
class ApprovalTaskHandler extends BaseTaskHandler
{
    /**
     * Create an approval task.
     */
    public function create(CreateTaskData $data): Task
    {
        // Ensure action type is set to Approval
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
     * Complete all approval tasks for a given model.
     *
     * Checks both full class name and snake_case variants to handle
     * legacy data with different morph type formats.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function completeForModel($model, string $reason): void
    {
        $morphClass = $model->getMorphClass();
        $snakeCaseClass = Str::snake(class_basename($model));

        $tasks = Task::query()
            ->with('users')
            ->where('action_type', ActionType::Approval)
            ->whereNull('completed_at')
            ->where(function ($query) use ($model, $morphClass, $snakeCaseClass) {
                $query->where(function ($q) use ($model, $morphClass) {
                    $q->where('taskable_type', $morphClass)
                        ->where('taskable_id', $model->getKey());
                })->orWhere(function ($q) use ($model, $snakeCaseClass) {
                    $q->where('taskable_type', $snakeCaseClass)
                        ->where('taskable_id', $model->getKey());
                });
            })
            ->get();

        foreach ($tasks as $task) {
            $this->complete($task, $reason);
        }
    }
}
