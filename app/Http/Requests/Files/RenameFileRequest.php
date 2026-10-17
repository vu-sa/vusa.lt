<?php

namespace App\Http\Requests\Files;

use Illuminate\Contracts\Validation\ValidationRule;

/** Renames a file or folder in place; the name rule matches CreateDirectoryRequest. */
class RenameFileRequest extends FilePathRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\p{C}\/\\\\]+$/u', 'not_in:.,..'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => __('files.validation.name_required'),
            'name.max' => __('files.validation.directory_name_max'),
            'name.regex' => __('files.validation.name_regex'),
        ]);
    }
}
