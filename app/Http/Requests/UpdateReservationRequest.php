<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Currently reservations are not updated directly - everything goes through reservation resources
        // In the future, we might want to allow updating reservation name/description directly
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // TODO: Add validation rules when direct reservation updates are implemented
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
        ];
    }
}
