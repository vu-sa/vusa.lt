<?php

namespace App\States\Doing;

class PendingCompletion extends DoingState
{
    public static $name = 'pending_completion';

    public function color(): string
    {
        return 'green';
    }

    public function handleProgress (): void
    {
        abort(403, 'Veikla jau yra pateikta užbaigimui.');
    }

    public function handleApprove (): void
    {
        $this->transitionTo(Completed::class);
    }

    public function handleReject (): void
    {
        //
    }

    public function handleCancel (): void
    {
        //
    }

}