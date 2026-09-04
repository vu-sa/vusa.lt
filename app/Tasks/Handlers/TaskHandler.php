<?php

namespace App\Tasks\Handlers;

use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;

interface TaskHandler
{
    public function create(CreateTaskData $data): Task;

    /**
     * @param  Task  $task
     */
    public function complete($task, ?string $reason = null): void;
}
