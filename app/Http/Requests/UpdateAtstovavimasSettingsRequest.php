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
            'institution_manager_role_id' => 'nullable|string|exists:roles,id',
            'student_rep_root_type_id' => 'nullable|integer|exists:types,id',
        ];
    }
}
