<?php

namespace App\Http\Requests;

use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreReservationResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Resource::class, $this->authorizer]);
    }

    protected function prepareForValidation() {
        $this->merge([
            'start_time' => Carbon::createFromTimestampMs($this->input('start_time')),
            'end_time' => Carbon::createFromTimestampMs($this->input('end_time')),
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
            'reservation_id' => 'required|string',
            'resource_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ];
    }
}
