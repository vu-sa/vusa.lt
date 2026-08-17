<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class MergeTagsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'target_tag_id' => ['required', 'integer', SoftDeleteRules::existsLive('tags')],
            'source_tag_ids' => 'required|array|min:1',
            'source_tag_ids.*' => ['integer', SoftDeleteRules::existsLive('tags')],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'target_tag_id.required' => trans('forms.validation.merge.target_required'),
            'target_tag_id.exists' => trans('forms.validation.merge.target_exists'),
            'source_tag_ids.required' => trans('forms.validation.merge.sources_required'),
            'source_tag_ids.min' => trans('forms.validation.merge.sources_min'),
            'source_tag_ids.*.exists' => trans('forms.validation.merge.sources_exist'),
        ];
    }
}
