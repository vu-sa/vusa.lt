<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;
use App\Settings\SettingsSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($this->user());
    }

    public function rules(): array
    {
        return [
            'type_ids' => 'nullable|array',
            'type_ids.*' => ['integer', SoftDeleteRules::existsLive('types')],
            'excluded_type_ids' => 'nullable|array',
            'excluded_type_ids.*' => ['integer', SoftDeleteRules::existsLive('types')],
        ];
    }
}
