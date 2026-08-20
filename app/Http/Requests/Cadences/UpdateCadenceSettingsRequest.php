<?php

namespace App\Http\Requests\Cadences;

use App\Settings\SettingsSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCadenceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($this->user());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // `MM-DD` only — the year comes from whichever term is being prefilled.
            'default_start_month_day' => ['required', 'string', 'date_format:m-d'],
            'default_end_month_day' => ['required', 'string', 'date_format:m-d'],
        ];
    }
}
