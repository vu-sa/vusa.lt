<?php

namespace App\Http\Requests;

use App\Settings\SettingsSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'recommendations' => 'present|array|max:20',
            'recommendations.*' => 'array:document_id,phrases,enabled,show_without_query',
            'recommendations.*.document_id' => ['required', 'integer', 'distinct', Rule::exists('documents', 'id')->where('is_active', true)],
            'recommendations.*.phrases' => 'present|array|max:10',
            'recommendations.*.phrases.*' => ['required', 'string', 'max:200', 'regex:/[\p{L}\p{N}]/u'],
            'recommendations.*.enabled' => 'required|boolean',
            'recommendations.*.show_without_query' => 'required|boolean',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach ($this->validated('recommendations') as $index => $rule) {
                if ($rule['enabled'] && ! $rule['show_without_query'] && collect($rule['phrases'])->every(fn (string $phrase) => trim($phrase) === '')) {
                    $validator->errors()->add("recommendations.$index.phrases", __('settings.document_settings.phrases_required'));
                }
            }
        }];
    }
}
