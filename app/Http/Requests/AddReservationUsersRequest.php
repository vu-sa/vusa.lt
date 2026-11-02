<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddReservationUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('addUsers', $this->reservation()) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'users' => ['required', 'array', 'min:1'],
            'users.*' => ['required', 'string', 'distinct', 'exists:users,id'],
        ];
    }

    public function reservation(): Reservation
    {
        /** @var Reservation $reservation */
        $reservation = $this->route('reservation');

        return $reservation;
    }
}
