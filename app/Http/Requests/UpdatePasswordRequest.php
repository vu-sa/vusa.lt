<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', function ($attribute, $value, $fail): void {
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
    #[\Override]
    public function messages(): array
    {
        return [
            'current_password.required' => trans('users.validation.current_password_required'),
            'current_password.string' => trans('users.validation.current_password_string'),
            'password.required' => trans('users.validation.password_required'),
            'password.confirmed' => trans('users.validation.password_confirmed'),
            'password_confirmation.required' => trans('users.validation.password_confirmation_required'),
        ];
    }
}
