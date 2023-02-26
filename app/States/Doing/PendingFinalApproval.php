<?php

namespace App\States\Doing;

class PendingFinalApproval extends DoingState
{
    public static $name = 'pending_final_approval';

    public function color(): string
    {
        return 'yellow';
    }

    public function handleProgress(): void
    {
        abort(403, 'Veikla jau yra pateikta galutiniam patvirtinimui.');
    }

    public function handleApprove(): void
    {
        $this->transitionTo(Approved::class);
    }

    public function handleReject(): void
    {
        $this->transitionTo(PendingChanges::class);
    }

    public function handleCancel(): void
    {
        $this->transitionTo(Cancelled::class);
    }
}
