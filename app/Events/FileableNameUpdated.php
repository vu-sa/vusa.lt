<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileableNameUpdated
{
    use Dispatchable, InteractsWithSockets;

    public $fileable;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($fileable)
    {
        $this->fileable = $fileable;
    }
}
