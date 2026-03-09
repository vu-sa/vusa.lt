<?php

namespace App\States\ReservationResource;

class Reserved extends ReservationResourceState
{
    public static $name = 'reserved';

    public function tagType(): string
    {
        return 'success';
    }

    public function description(): string
    {
        return __('state.description.reservation_resource.reserved');
    }

    public function handleApprove(): void
    {
        $this->transitionTo(Lent::class);
    }

    public function handleReject(): void
    {
        // This should never be called - validation in isDecisionAllowed prevents it
    }

    public function handleCancel(): void
    {
        $this->transitionTo(Cancelled::class);
    }
}
