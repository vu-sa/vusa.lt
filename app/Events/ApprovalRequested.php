<?php

namespace App\Events;

use App\Contracts\Approvable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when approval is requested for a model.
 * Triggers task creation and notification to approvers.
 */
class ApprovalRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model&Approvable  $approvable  The model requiring approval
     * @param  int  $step  The step number for which approval is requested (1-indexed)
     */
    public function __construct(
        public Model $approvable,
        public int $step = 1
    ) {}
}
