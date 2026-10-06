<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class ProblemCategoryRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required|string|max:255',
            'name.en' => 'nullable|string|max:255',
            'description.lt' => 'nullable|string|max:500',
            'description.en' => 'nullable|string|max:500',
        ];
    }
}
