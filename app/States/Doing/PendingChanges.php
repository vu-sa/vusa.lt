<?php

namespace App\States\Doing;

class PendingChanges extends DoingState
{
    public static $name = 'pending_changes';

    public function color(): string
    {
        return 'yellow';
    }

    public function handleProgress (): void
    {
        $this->transitionTo(PendingPadalinysApproval::class);
    }

    public function handleApprove (): void
    {
        abort(403, 'Veikla pirmiausiai turi būti pateikta tvirtinimui.');
    }

    public function handleReject (): void
    {
        abort(403, 'Veikla pirmiausiai turi būti pateikta tvirtinimui.');
    }

    public function handleCancel (): void
    {
        $this->transitionTo(Cancelled::class);
    }
}