<?php

namespace App\States\ReservationResource;

class Created extends ReservationResourceState
{
    public static $name = 'created';

    public function tagType(): string
    {
        return 'info';
    }

    public function description(): string
    {
        return __('state.description.reservation_resource.created');
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
