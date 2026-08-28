<?php

namespace App\Http\Requests\Files;

use Illuminate\Contracts\Validation\ValidationRule;

class CreateDirectoryRequest extends FilePathRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // A single segment, matching what the browser already shows on disk: commas,
            // brackets, quotes and dashes are all in use in existing folder names, so the old
            // letters/digits/underscore/hyphen allowlist could not even reproduce them. What
            // stays banned is what cannot be a name — separators, traversal, and the control
            // and format characters \p{C} covers.
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
            'name.required' => __('files.validation.directory_name_required'),
            'name.max' => __('files.validation.directory_name_max'),
            'name.regex' => __('files.validation.directory_name_regex'),
        ]);
    }
}
