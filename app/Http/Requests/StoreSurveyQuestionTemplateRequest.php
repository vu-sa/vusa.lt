<?php

namespace App\Http\Requests;

use App\Enums\SurveyQuestionType;
use App\Models\SurveyQuestionTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurveyQuestionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SurveyQuestionTemplate::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Null means the template is shared with every tenant.
            'tenant_id' => ['nullable', 'exists:tenants,id'],

            'title' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/'],
            'type' => ['required', Rule::enum(SurveyQuestionType::class)],

            'group_name' => ['required', 'array'],
            'group_name.lt' => ['required', 'string', 'max:255'],
            'group_name.en' => ['nullable', 'string', 'max:255'],

            'question' => ['required', 'array'],
            'question.lt' => ['required', 'string'],
            'question.en' => ['nullable', 'string'],

            'help' => ['nullable', 'array'],
            'help.lt' => ['nullable', 'string'],
            'help.en' => ['nullable', 'string'],

            'options' => ['nullable', 'array', 'max:50'],
            'options.*.code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_]+$/'],
            'options.*.label' => ['required', 'array'],
            'options.*.label.lt' => ['required', 'string', 'max:255'],
            'options.*.label.en' => ['nullable', 'string', 'max:255'],

            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ];
    }
}
