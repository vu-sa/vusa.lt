<?php

namespace App\States\ReservationResource;

class Reserved extends ReservationResourceState
{
    public static $name = 'reserved';

    public function color(): string
    {
        return 'green';
    }

    public function handleProgress(): void
    {
        $this->transitionTo(Lent::class);
    }

    public function handleApprove(): void
    {
        abort(403, 'Užrezervuoto ištekliaus jau nebereikia patvirtinti.')
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
