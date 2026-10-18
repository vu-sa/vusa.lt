<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasImageValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Self-service profile fields only — never name (super admins rename people, which keeps
 * name matching trustworthy for merges) or email/password, which have their own flows.
 */
class UpdateUserSettingsRequest extends FormRequest
{
    use HasImageValidation;

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
            'phone' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            ...$this->imageMediaRules('profile_photo_media', $this->user(), 'profile_photo'),
            'pronouns' => ['nullable', 'array'],
            'show_pronouns' => ['nullable', 'boolean'],
        ];
    }
}
