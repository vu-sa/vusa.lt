<?php

namespace App\States\ReservationResource;

class Rejected extends ReservationResourceState
{
    public static $name = 'rejected';

    public function tagType(): string
    {
        return 'error';
    }

    public function description(): string
    {
        return __('state.description.reservation_resource.rejected');
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
