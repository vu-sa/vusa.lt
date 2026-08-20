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
            'name' => 'required|string|max:255|regex:/^[\p{L}\p{N}_\- ]+$/u',
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
