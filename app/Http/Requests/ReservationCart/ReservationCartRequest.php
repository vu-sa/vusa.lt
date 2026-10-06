<?php

namespace App\Http\Requests\ReservationCart;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The cart is always the acting user's own draft, so the only gate is being allowed to reserve.
 */
abstract class ReservationCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Reservation::class) ?? false;
    }
}
