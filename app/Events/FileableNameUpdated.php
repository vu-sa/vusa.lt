<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

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
