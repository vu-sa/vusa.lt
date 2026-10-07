<?php

namespace App\Http\Requests;

use App\Models\Reservation;

class StoreReservationResourceRequest extends ReservationResourceRequest
{
    public function authorize(): bool
    {
        $id = $this->input('reservation_id');
        $reservation = is_string($id) ? Reservation::query()->find($id) : null;

        return $reservation === null || $this->user()->can('update', $reservation);
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'reservation_id' => ['required', 'string', 'exists:reservations,id'],
        ];
    }
}
