<?php

namespace App\States\ReservationResource;

class Rejected extends ReservationResourceState
{
    public static $name = 'rejected';

    public function color(): string
    {
        return 'red';
    }

    public function handleProgress(): void
    {
        // do nothing
    }

    public function handleApprove(): void
    {
        // do nothing
    }

    public function handleReject(): void
    {
        // do nothing
    }

    public function handleCancel(): void
    {
        // do nothing
    }
}
