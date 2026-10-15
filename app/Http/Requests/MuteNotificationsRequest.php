<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Self-scoped: mutes or unmutes the acting user's own notifications. `hours: null` unmutes.
 */
class MuteNotificationsRequest extends FormRequest
{
    public const array HOUR_OPTIONS = [1, 4, 24, 168];

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
            'hours' => ['present', 'nullable', 'integer', Rule::in(self::HOUR_OPTIONS)],
        ];
    }
}
