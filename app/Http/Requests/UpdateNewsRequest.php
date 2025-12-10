<?php

namespace App\Http\Requests;

use App\Enums\ContentPartEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNewsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->news);
    }

    protected function prepareForValidation()
    {
        $publishTime = $this->input('publish_time');

        // Only process publish_time if it's not null
        if ($publishTime !== null) {
            $this->merge([
                'publish_time' => is_string($publishTime)
                    ? strtotime($publishTime)
                    : $publishTime / 1000,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'lang' => 'required|string',
            'other_lang_id' => 'nullable|integer',
            'draft' => 'nullable|boolean',
            'short' => 'nullable',
            'image' => 'nullable|string',
            'image_author' => 'nullable|string',
            'publish_time' => 'required',
            'permalink' => 'required|string|unique:news,permalink,'.$this->news->id,
            'layout' => 'nullable|string|in:modern,classic,immersive,headline',
            'highlights' => 'nullable|array|max:3',
            'highlights.*' => 'nullable|string|max:500',
            'content' => 'required|array',
            'content.parts' => 'required|array',
            'content.parts.*.id' => 'nullable|integer',
            'content.parts.*.type' => ['required', 'string', Rule::in(ContentPartEnum::toArray())],
            'content.parts.*.json_content' => 'required',
            'content.parts.*.options' => 'nullable',
            'content.parts.*.order' => 'nullable|integer',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
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
