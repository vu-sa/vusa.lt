<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Reservation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResourceAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Availability is only meaningful within the reservation-creation flow,
     * so gate on the same ability the create form uses.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Reservation::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['required', 'string'],
            'start' => ['required', 'integer', 'lt:end'],
            'end' => ['required', 'integer'],
        ];
    }
}
