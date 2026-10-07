<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

abstract class ReservationResourceRequest extends FormRequest
{
    public function rules(): array
    {
        // MySQL datetime columns support years 1000–9999.
        $minimum = Carbon::create(1000, 1, 1, 0, 0, 0, 'Europe/Vilnius')->getTimestampMs();
        $maximum = Carbon::create(9999, 12, 31, 23, 59, 59, 'Europe/Vilnius')->getTimestampMs();

        return [
            'resource_id' => ['required', 'string', SoftDeleteRules::existsLive('resources')],
            'quantity' => ['required', 'integer', 'min:1'],
            'start_time' => ['required', 'integer', "between:{$minimum},{$maximum}"],
            'end_time' => ['required', 'integer', "between:{$minimum},{$maximum}", 'gt:start_time'],
        ];
    }

    public function resourceAttributes(): array
    {
        return [
            'resource_id' => $this->validated('resource_id'),
            'quantity' => (int) $this->validated('quantity'),
            'start_time' => Carbon::createFromTimestampMs($this->validated('start_time'), 'Europe/Vilnius'),
            'end_time' => Carbon::createFromTimestampMs($this->validated('end_time'), 'Europe/Vilnius'),
        ];
    }
}
