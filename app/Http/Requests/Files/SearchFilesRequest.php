<?php

namespace App\Http\Requests\Files;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Recursive file search beneath one directory.
 *
 * `path` is optional here (unlike FilePathRequest) because searching from the manager's root
 * is the common case. Authorization stays in the controller: it depends on the normalized
 * path, and every directory the walk descends into is checked separately.
 */
class SearchFilesRequest extends FormRequest
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
            // Two characters is the floor: a single letter matches most of a 600-file directory
            // and turns every keystroke into a full-tree walk for nothing.
            'q' => 'required|string|min:2|max:100',
            'path' => 'nullable|string',
            'extensions' => 'nullable|string|max:255',
        ];
    }

    public function searchTerm(): string
    {
        return trim((string) $this->validated('q'));
    }
}
