<?php

namespace App\Events;

use App\Contracts\Approvable;
use App\Models\Approval;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an approval decision is made.
 * Triggers task auto-completion and notifications.
 */
class ApprovalDecisionMade
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Approval  $approval  The approval record that was created
     * @param  Model&Approvable  $approvable  The model that was approved/rejected
     */
    public function __construct(
        public Approval $approval,
        public Model $approvable
    ) {}
}
