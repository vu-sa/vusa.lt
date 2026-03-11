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
}
