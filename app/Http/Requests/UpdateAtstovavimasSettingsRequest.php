<?php

namespace App\Http\Requests;

use App\Settings\SettingsSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAtstovavimasSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($this->user());
    }

    public function rules(): array
    {
        return [
            'coordinator_role_ids' => 'nullable|array',
            'coordinator_role_ids.*' => 'string|exists:roles,id',
        ];
    }
}
