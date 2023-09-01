<?php

namespace App\Events;

use App\Models\RoleType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
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
