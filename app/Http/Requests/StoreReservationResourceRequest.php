<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreReservationResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * ReservationPolicy::create() is true for everyone, so a class-level check alone would let
     * any user attach resources to a reservation that isn't theirs. The owning reservation
     * comes from request input, so it has to be authorized as an object — the same `update`
     * ability ReservationResourceController already applies to update/destroy.
     */
    public function authorize(): bool
    {
        $reservation = Reservation::query()->find($this->input('reservation_id'));

        // Let the exists rule below report a missing reservation as a validation error rather
        // than masking it as a 403.
        if ($reservation === null) {
            return true;
        }

        return $this->user()->can('update', $reservation);
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'start_time' => Carbon::createFromTimestampMs($this->input('start_time'), 'Europe/Vilnius'),
            'end_time' => Carbon::createFromTimestampMs($this->input('end_time'), 'Europe/Vilnius'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'reservation_id' => ['required', 'string', 'exists:reservations,id'],
            'resource_id' => ['required', 'string', 'exists:resources,id'],
            'quantity' => 'required|integer|min:1',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ];
    }
}
