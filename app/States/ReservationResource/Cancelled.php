<?php

namespace App\States\ReservationResource;

class Cancelled extends ReservationResourceState
{
    public static $name = 'cancelled';

    public function tagType(): string
    {
        return 'error';
    }

    public function description(): string
    {
        return __('state.description.reservation_resource.cancelled');
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
