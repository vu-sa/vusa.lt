<?php

namespace App\Tasks\Handlers;

use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;

/**
 * Handles Manual tasks that can be completed by users at any time.
 *
 * Manual tasks are the simplest task type - they have no auto-completion
 * triggers and are marked complete by users directly.
 */
class ManualTaskHandler extends BaseTaskHandler
{
    /**
     * Create a manual task.
     */
    public function create(CreateTaskData $data): \App\Models\Task
    {
        // Ensure action type is set to Manual (or null for legacy compatibility)
        $data = new CreateTaskData(
            name: $data->name,
            taskable: $data->taskable,
            users: $data->users,
            dueDate: $data->dueDate,
            actionType: $data->actionType ?? ActionType::Manual,
            metadata: $data->metadata,
        );

        return parent::create($data);
    }
}
