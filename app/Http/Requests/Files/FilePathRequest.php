<?php

namespace App\Http\Requests\Files;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared shape for the file-manager actions that operate on a single `path`.
 *
 * Authorization is deliberately left to the controller: it depends on the *normalized* path
 * (`FilesController::validateAndNormalizePath()`), which only exists after validation has run,
 * and is checked there with the ability matching the action — `viewDirectory` for reads,
 * `createInDirectory` for adding a file or folder, `updateInDirectory` for rewriting a file,
 * `deleteInDirectory` for deleting one, and `deleteDirectory` for removing the folder itself.
 */
class FilePathRequest extends FormRequest
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
            'path' => 'required|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'path.required' => __('files.validation.path_required'),
        ];
    }
}
