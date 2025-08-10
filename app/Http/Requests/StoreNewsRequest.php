<?php

namespace App\Http\Requests;

use App\Enums\ContentPartEnum;
use App\Models\News;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreNewsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', News::class);
    }

    protected function prepareForValidation()
    {
        $publishTime = $this->input('publish_time');

        // Only process publish_time if it's not null
        if ($publishTime !== null) {
            $this->merge([
                'publish_time' => is_string($publishTime)
                    ? Carbon::createFromTimestamp(strtotime($publishTime), 'Europe/Vilnius')
                    : Carbon::createFromTimestampMs($publishTime, 'Europe/Vilnius'),
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
            'permalink' => 'required|unique:news,permalink',
            'content.parts' => 'required',
            'lang' => 'required',
            'image' => 'required',
            'publish_time' => 'required',
            'short' => 'required',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'other_lang_id' => 'nullable|integer',
            'draft' => 'nullable|boolean',
            'image_author' => 'nullable|string',
            'content' => 'required|array',
            'content.parts' => 'required|array',
            'content.parts.*.id' => 'nullable|integer',
            'content.parts.*.type' => ['required', 'string', Rule::in(ContentPartEnum::toArray())],
            'content.parts.*.json_content' => 'required',
            'content.parts.*.options' => 'nullable',
            'content.parts.*.order' => 'nullable|integer',
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