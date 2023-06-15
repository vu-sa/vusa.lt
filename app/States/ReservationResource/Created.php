<?php

namespace App\States\ReservationResource;

class Created extends ReservationResourceState
{
    public static $name = 'created';

    public function color(): string
    {
        return 'yellow';
    }

    public function handleProgress(): void
    {
        $this->transitionTo(Updated::class);
    }

    public function handleApprove(): void
    {
        $this->transitionTo(Reserved::class);
    }

    public function handleReject(): void
    {
        $this->transitionTo(Rejected::class);
    }

    public function handleCancel(): void
    {
        $this->transitionTo(Cancelled::class);
    }
}
