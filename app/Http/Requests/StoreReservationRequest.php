<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Reservation::class);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            // check if int, if not, use input
            'start_time' => is_int($this->input('start_time')) ? Carbon::createFromTimestampMs($this->input('start_time'), 'Europe/Vilnius') : $this->input('start_time'),
            'end_time' => is_int($this->input('end_time')) ? Carbon::createFromTimestampMs($this->input('end_time'), 'Europe/Vilnius') : $this->input('end_time'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',
            'resources' => 'required|array',
            'resources.*.id' => 'required|string|exists:resources,id',
            'resources.*.quantity' => 'required|integer|min:1',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ];
    }
}
