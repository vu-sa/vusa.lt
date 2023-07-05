<?php

namespace App\States\ReservationResource;

class Returned extends ReservationResourceState
{
    public static $name = 'returned';

    public function tagType(): string
    {
        return 'success';
    }

    public function description(): string
    {
        return 'Daiktas sėkmingai grąžintas rezervacijos organizatorių.';
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
