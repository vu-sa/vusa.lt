<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSurveyRequest extends FormRequest
{
    /**
     * The policy refuses the update outright once the survey exists in LimeSurvey, so
     * there is no separate "locked" check to keep in sync here.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('survey'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],

            'name' => ['required', 'array'],
            'name.lt' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.lt' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],

            'welcome_text' => ['nullable', 'array'],
            'welcome_text.lt' => ['nullable', 'string'],
            'welcome_text.en' => ['nullable', 'string'],

            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],

            'is_anonymous' => ['boolean'],
        ];
    }
}
