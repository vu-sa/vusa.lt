<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesContentParts;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class NewsRequest extends FormRequest
{
    use ValidatesContentParts;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->contentPartRules(),
            'title' => 'required',
            'lang' => 'required',
            'other_lang_id' => ['nullable', 'integer', SoftDeleteRules::existsLive('news')],
            'draft' => 'nullable|boolean',
            'image_author' => 'nullable|string',
            'publish_time' => 'required',
            'layout' => 'nullable|string|in:modern,classic,immersive,headline',
            'show_breadcrumbs' => ['boolean'],
            'highlights' => 'nullable|array|max:3',
            'highlights.*' => 'nullable|string|max:500',
            'content' => 'required|array',
            'tags' => 'nullable|array',
            'tags.*' => ['integer', SoftDeleteRules::existsLive('tags')],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'The content is required.',
            'content.parts.required' => 'The content parts are required.',
            'content.parts.*.type.required' => 'Each content part must have a type.',
            'content.parts.*.type.exists' => 'The selected content part type is invalid.',
            'content.parts.*.json_content.required' => 'Each content part must have content.',
        ];
    }
}
