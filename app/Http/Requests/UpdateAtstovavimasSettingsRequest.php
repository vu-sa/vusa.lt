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
            'global_visibility_role_ids' => 'nullable|array',
            'global_visibility_role_ids.*' => 'string|exists:roles,id',
            'tenant_visibility_role_ids' => 'nullable|array',
            'tenant_visibility_role_ids.*' => 'string|exists:roles,id',
        ];
    }
}
