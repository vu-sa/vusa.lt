<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The assigner travels with the event: the listener is queued, where auth() is empty.
     */
    public function __construct(public Task $task, public ?User $assigner = null) {}
}
