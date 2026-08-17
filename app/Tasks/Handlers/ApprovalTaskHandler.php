<?php

namespace App\Tasks\Handlers;

use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;

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
    #[\Override]
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
     * This used to query the class name and its snake_case spelling separately, because
     * taskable_type held whichever the writing code happened to produce. The morph map makes
     * the alias the only spelling, so one condition covers everything.
     *
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
