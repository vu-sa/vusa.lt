<?php

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

class IndexFilesRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'path' => 'nullable|string',
            'extensions' => 'nullable|string|max:255',
        ];
    }
}
