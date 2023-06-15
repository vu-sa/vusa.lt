<?php

namespace App\States\ReservationResource;

class Returned extends ReservationResourceState
{
    public static $name = 'returned';

    public function color(): string
    {
        return 'green';
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
