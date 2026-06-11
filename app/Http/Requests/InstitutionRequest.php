<?php

namespace App\Http\Requests;

use App\Enums\DutySelectionMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstitutionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required',
            'short_name.lt' => 'nullable',
            'description.lt' => 'nullable',
            'name.en' => 'nullable',
            'short_name.en' => 'nullable',
            'description.en' => 'nullable',
            'address.lt' => 'nullable|string',
            'address.en' => 'nullable|string',
            'website' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'tenant_id' => 'required',
            'image_url' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'facebook_url' => 'nullable|string',
            'instagram_url' => 'nullable|string',
            'is_active' => 'boolean',
            'contacts_layout' => 'required|in:aside,below',
            'types' => 'nullable|array',
            'meeting_periodicity_days' => 'nullable|integer|min:1|max:365',
            'selection_method' => ['nullable', Rule::enum(DutySelectionMethod::class)],
            'appointed_by' => 'nullable|array',
            'term_length' => 'nullable|array',
        ];
    }
}
