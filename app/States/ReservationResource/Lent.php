<?php

namespace App\States\ReservationResource;

class Lent extends ReservationResourceState
{
    public static $name = 'lent';

    public function tagType(): string
    {
        return 'warning';
    }

    public function description(): string
    {
        return __('state.description.reservation_resource.lent');
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
