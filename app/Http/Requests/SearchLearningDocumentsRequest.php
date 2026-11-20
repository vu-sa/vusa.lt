<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchLearningDocumentsRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }
}
