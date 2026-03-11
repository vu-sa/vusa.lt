<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTextBoxSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'content_part_id' => ['required', 'integer', 'exists:content_parts,id'],
            'text' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'text.required' => __('Atsakymas yra privalomas.'),
            'text.min' => __('Atsakymas turi būti bent 10 simbolių.'),
            'text.max' => __('Atsakymas negali būti ilgesnis nei 5000 simbolių.'),
        ];
    }
}
