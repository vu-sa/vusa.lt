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
    #[\Override]
    public function messages(): array
    {
        return [
            'content.required' => trans('forms.validation.content.required'),
            ...$this->contentPartMessages(),
        ];
    }
}
