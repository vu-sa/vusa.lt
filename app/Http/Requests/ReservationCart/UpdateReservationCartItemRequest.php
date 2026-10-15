<?php

namespace App\Http\Requests\ReservationCart;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateReservationCartItemRequest extends ReservationCartRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
