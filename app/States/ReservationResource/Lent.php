<?php

namespace App\States\ReservationResource;

class Lent extends ReservationResourceState
{
    public static $name = 'lent';

    public function color(): string
    {
        return 'orange';
    }

    public function handleProgress(): void
    {
        abort(403, 'Paskolintas daiktas gali būti tik grąžintas.');
    }

    public function handleApprove(): void
    {
        $this->transitionTo(Returned::class);
    }

    public function handleReject(): void
    {
        abort(403, 'Paskolintas daiktas negali būti atmestas.');
    }

    public function handleCancel(): void
    {
        abort(403, 'Paskolintas daiktas negali būti atšauktas.');
    }
}
