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
        return 'Išteklius rezervuotas! Rezervuotą daiktą galima atsiimti nurodytu laiku.';
    }

    public function handleApprove(): void
    {
        $this->transitionTo(Lent::class);
    }

    public function handleReject(): void
    {
        abort(403, 'Užrezervuoto ištekliaus negalima atmesti.');
    }

    public function handleCancel(): void
    {
        $this->transitionTo(Cancelled::class);
    }
}
