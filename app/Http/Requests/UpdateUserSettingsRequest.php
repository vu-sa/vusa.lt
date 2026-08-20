<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Self-service profile fields only — never email/password, which have their own dedicated
 * flows (UpdatePasswordRequest).
 */
class UpdateUserSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'profile_photo_path' => ['nullable', 'string'],
            'profile_photo_focal_point' => ['nullable', 'array'],
            'pronouns' => ['nullable', 'array'],
            'show_pronouns' => ['nullable', 'boolean'],
        ];
    }
}
