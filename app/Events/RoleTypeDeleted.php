<?php

namespace App\Events;

use App\Models\RoleType;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleTypeDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roleType;

    /**
     * Create a new event instance.
     */
    public function __construct(RoleType $roleType)
    {
        $this->roleType = $roleType;
    }
}
