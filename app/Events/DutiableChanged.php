<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DutiableChanged
{
    use Dispatchable, SerializesModels;

    public $dutiable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($dutiable)
    {
        $this->dutiable = $dutiable;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    // public function broadcastOn()
    // {
    //     return new PrivateChannel('channel-name');
    // }
}
