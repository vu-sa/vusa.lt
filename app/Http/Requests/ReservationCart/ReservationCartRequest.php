<?php

namespace App\Http\Requests\ReservationCart;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The cart is always the acting user's own draft, so the only gate is being allowed to reserve.
 */
abstract class ReservationCartRequest extends FormRequest
{
}
