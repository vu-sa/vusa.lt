<?php

namespace App\Events;

use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an approval flow is completed (approved, rejected, or cancelled).
 * Triggers final state transitions and notifications.
 */
class ApprovalFlowCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model&Approvable  $approvable  The model whose approval flow completed
     * @param  ApprovalDecision  $decision  The final decision (approved/rejected/cancelled)
     */
    public function __construct(
        public Model $approvable,
        public ApprovalDecision $decision
    ) {}
}
