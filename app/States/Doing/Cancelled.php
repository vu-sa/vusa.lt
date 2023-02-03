<?php

namespace App\States\Doing;

class Cancelled extends DoingState
{
    public static $name = 'cancelled';

    public function color(): string
    {
        return 'red';
    }

    public function handleProgress (): void
    {
        // do nothing
    }

    public function handleApprove (): void
    {
        // do nothing
    }

    public function handleReject (): void
    {
        // do nothing
    }

    public function handleCancel (): void
    {
        // do nothing
    }
}