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
        $this->merge([
            'publish_time' => is_string($this->input('publish_time'))
                ? strtotime($this->input('publish_time'))
                : $this->input('publish_time') / 1000,
        ]);
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
