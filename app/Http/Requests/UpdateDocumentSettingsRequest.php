<?php

namespace App\Http\Requests;

use App\Settings\SettingsSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($this->user());
    }

    public function rules(): array
    {
        return [
            'important_content_types' => 'nullable|array',
            'important_content_types.*' => 'string|max:255',
        ];
    }
}
