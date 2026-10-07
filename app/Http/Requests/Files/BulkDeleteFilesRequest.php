<?php

namespace App\Http\Requests\Files;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Each destination needs its directory's delete permission before any file is removed. */
class BulkDeleteFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'paths' => 'required|array|min:1|max:50', // Limit to 50 files for safety
            'paths.*' => 'required|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paths.required' => __('files.validation.paths_required'),
            'paths.min' => __('files.validation.paths_required'),
            'paths.max' => __('files.validation.paths_max'),
        ];
    }
}
