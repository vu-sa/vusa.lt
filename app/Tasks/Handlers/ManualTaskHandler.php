<?php

namespace App\Tasks\Handlers;

use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;

class ManualTaskHandler extends BaseTaskHandler
{
    #[\Override]
    public function create(CreateTaskData $data): Task
    {
        // Preserve null for legacy compatibility.
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
