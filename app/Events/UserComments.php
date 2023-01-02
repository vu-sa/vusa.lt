<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserComments
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $commenter;
    public $modelCommentedOn;
    public $route;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $commenter, $modelCommentedOn, $route)
    {
        $this->commenter = $commenter;
        $this->modelCommentedOn = $modelCommentedOn;
        $this->route = $route;
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    // public function broadcastOn()
    // {
    //     // return new PrivateChannel('channel-name');
    // }
}
