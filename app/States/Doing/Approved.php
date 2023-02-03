<?php

namespace App\States\Doing;

class Approved extends DoingState
{
    public static $name = 'approved';

    public function color(): string
    {
        return 'green';
    }

    public function handleProgress (): void
    {
        $this->transitionTo(PendingCompletion::class);
    }

    public function handleApprove (): void
    {
        abort(403, 'Negalima patvirtinti jau patvirtinto darbo.');
    }

    public function handleReject (): void
    {
        abort(403, 'Negalima atmesti jau patvirtinto darbo.');
    }

    public function handleCancel (): void
    {
        $this->transitionTo(Cancelled::class);
    }
}