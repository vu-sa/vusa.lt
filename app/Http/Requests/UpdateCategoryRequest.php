<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name.lt' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'alias' => 'nullable|string|max:255|unique:categories,alias,'.$categoryId,
        ];
    }
}
