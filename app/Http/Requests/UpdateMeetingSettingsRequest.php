<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'type_ids' => 'nullable|array',
            'type_ids.*' => 'integer|exists:types,id',
        ];
    }
}
