<?php

namespace App\Http\Requests\ReservationCart;

use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreReservationCartItemRequest extends ReservationCartRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resource_id' => ['required', 'string', SoftDeleteRules::existsLive('resources')->where('is_reservable', true)],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
