<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->page);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content.parts' => 'required',
            'lang' => 'required|string|in:lt,en',
            'category_id' => 'nullable|exists:categories,id',
            'other_lang_id' => 'nullable|exists:pages,id|different:id',
            'is_active' => 'required|boolean',
            'layout' => 'nullable|string|in:default,wide,focused',
        ];
    }
}
