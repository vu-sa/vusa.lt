<?php

namespace App\Http\Requests;

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
            'target_tag_id' => 'required|integer|exists:tags,id',
            'source_tag_ids' => 'required|array|min:1',
            'source_tag_ids.*' => 'integer|exists:tags,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'target_tag_id.required' => 'Target tag is required.',
            'target_tag_id.exists' => 'Selected target tag does not exist.',
            'source_tag_ids.required' => 'At least one tag to merge is required.',
            'source_tag_ids.min' => 'At least one tag to merge must be selected.',
            'source_tag_ids.*.exists' => 'One or more selected tags do not exist.',
        ];
    }
}
