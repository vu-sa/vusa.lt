<?php

namespace App\Events;

use App\Models\Pivots\ReservationResource;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationResourceCreated
{
    use Dispatchable, SerializesModels;

    public $reservationResource;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ReservationResource $reservationResource)
    {
        $this->reservationResource = $reservationResource;
    }
}
