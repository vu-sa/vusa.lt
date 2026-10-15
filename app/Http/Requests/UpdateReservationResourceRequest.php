<?php

namespace App\Http\Requests;

class UpdateReservationResourceRequest extends ReservationResourceRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('reservationResource'));
    }
}
