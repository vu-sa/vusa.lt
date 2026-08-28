<?php

namespace App\Http\Requests\Files;

use Illuminate\Contracts\Validation\ValidationRule;

class UploadImageRequest extends FilePathRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'image' => 'nullable|image|max:51200', // 50MB max
            // The controller falls back to `file` when `image` is absent, so it needs the same
            // rules — otherwise an upload sent under `file` skipped both the image check and the
            // size cap entirely.
            'file' => 'nullable|image|max:51200',
            'name' => 'nullable|string|max:255',
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.max' => __('files.validation.file_name_max'),
            'image.image' => __('files.validation.image_invalid'),
            'image.max' => __('files.validation.image_max'),
            'file.image' => __('files.validation.image_invalid'),
            'file.max' => __('files.validation.image_max'),
        ]);
    }
}
