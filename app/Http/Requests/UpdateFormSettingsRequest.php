<?php

namespace App\Http\Requests;

use App\Settings\SettingsSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFormSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_registration_form_id' => 'required|ulid|exists:forms,id',
        ];
    }
}
