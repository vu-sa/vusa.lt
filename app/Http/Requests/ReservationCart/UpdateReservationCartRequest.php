<?php

namespace App\Http\Requests\ReservationCart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class UpdateReservationCartRequest extends ReservationCartRequest
{
    #[\Override]
    protected function prepareForValidation(): void
    {
        foreach (['start_time', 'end_time'] as $key) {
            if (is_int($this->input($key))) {
                $this->merge([$key => Carbon::createFromTimestampMs($this->input($key), 'Europe/Vilnius')]);
            }
        }
    }

    /**
     * Every field is optional: the browser saves the period and the checkout saves the texts,
     * each without resending the other.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'start_time' => ['sometimes', 'nullable', 'date', 'required_with:end_time'],
            'end_time' => ['sometimes', 'nullable', 'date', 'required_with:start_time', 'after:start_time'],
        ];
    }
}
