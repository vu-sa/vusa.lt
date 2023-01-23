<?php

namespace App\States\Doing;

class PendingPadalinysApproval extends DoingState
{
    public static $name = 'pending_padalinys_approval';

    public function color(): string
    {
        return 'green';
    }

    public function handleProgress (): void
    {
        abort(403, 'Veikla jau yra pateikta padalinio tvirtinimui.');
    }

    public function handleApprove (): void
    {
        $this->transitionTo(PendingFinalApproval::class);
    }

    public function handleReject (): void
    {
        $this->transitionTo(PendingChanges::class);
    }

    public function handleCancel (): void
    {
        $this->transitionTo(Cancelled::class);
    }
}