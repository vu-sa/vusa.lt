<?php

namespace App\Tasks\Handlers;

use App\Tasks\DTOs\CreateTaskData;

/**
 * Contract for task handler classes.
 *
 * Task handlers encapsulate the logic for creating, completing, and managing
 * tasks of a specific type (Approval, Pickup, Return, etc.).
 */
interface TaskHandler
{
    /**
     * Create a task using the provided data.
     *
     * @return \App\Models\Task
     */
    public function create(CreateTaskData $data);

    /**
     * Complete a task with an optional reason.
     *
     * @param  \App\Models\Task  $task
     */
    public function complete($task, ?string $reason = null): void;
}
