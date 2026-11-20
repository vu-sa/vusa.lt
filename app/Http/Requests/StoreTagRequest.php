<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'alias' => ['nullable', 'string', 'max:255', Rule::unique('tags', 'alias')->withoutTrashed()],
            'is_topic' => ['boolean'],
        ];
    }
}
