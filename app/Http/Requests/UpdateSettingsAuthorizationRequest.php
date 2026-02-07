<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only super admins can change settings authorization
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'settings_manager_role_id' => 'nullable|string|exists:roles,id',
        ];
    }
}
