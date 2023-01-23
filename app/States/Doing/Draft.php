<?php

namespace App\States\Doing;

class Draft extends DoingState
{
    public static $name = 'draft';

    public function color(): string
    {
        return 'gray';
    }

    public function handleProgress (): void
    {
        $this->transitionTo(PendingPadalinysApproval::class);
    }

    public function handleApprove (): void
    {
        abort(403, 'Negalima patvirtinti dar nepradėto darbo.');
    }

    public function handleReject (): void
    {
        abort(403, 'Negalima atmesti dar nepradėto darbo.');
    }

    public function handleCancel (): void
    {
        // 
    }
}