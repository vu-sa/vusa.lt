<?php

namespace App\Http\Requests;

use App\Enums\ContentPartEnum;
use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Page::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content.parts' => 'required|array',
            'content.parts.*.id' => 'nullable|integer',
            'content.parts.*.type' => ['required', 'string', Rule::in(ContentPartEnum::toArray())],
            'content.parts.*.json_content' => 'present',
            'content.parts.*.options' => 'nullable',
            'content.parts.*.order' => 'nullable|integer',
            'lang' => 'required|string|in:lt,en',
            'permalink' => 'required|string|max:255|unique:pages',
            'category_id' => 'nullable|exists:categories,id',
            'other_lang_id' => 'nullable|exists:pages,id',
            'is_active' => 'required|boolean',
            'layout' => 'nullable|string|in:default,wide,focused',
        ];
    }
}
