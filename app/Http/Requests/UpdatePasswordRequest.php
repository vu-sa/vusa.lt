<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', function ($attribute, $value, $fail) {
                $userPassword = $this->user()->password;

                if (is_null($userPassword)) {
                    $fail('Negalite pakeisti slaptažodžio, nes dabartinis slaptažodis nenustatytas.');

                    return;
                }

                if (! Hash::check($value, $userPassword)) {
                    $fail('Dabartinis slaptažodis neteisingas.');
                }
            }],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Dabartinis slaptažodis yra privalomas.',
            'current_password.string' => 'Dabartinis slaptažodis turi būti tekstas.',
            'password.required' => 'Naujas slaptažodis yra privalomas.',
            'password.confirmed' => 'Slaptažodžio patvirtinimas nesutampa.',
            'password_confirmation.required' => 'Slaptažodžio patvirtinimas yra privalomas.',
        ];
    }
}
