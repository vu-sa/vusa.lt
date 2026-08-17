<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFilesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * This is the coarse gate — the user must be allowed to create files at all. Which
     * *directory* they may write to is decided in FilesController::store(), because that
     * depends on the branch the path takes (tenant content tree vs. FileManager path).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', File::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array'],
            'files.*.file' => [
                'required',
                'file',
                'max:51200', // 50MB in KB
                'mimes:'.$this->getAllowedMimeTypes(),
            ],
            'path' => ['required', 'string'],
        ];
    }

    /**
     * Get allowed MIME types for website files
     */
    private function getAllowedMimeTypes(): string
    {
        return implode(',', [
            // Images
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            // Documents
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            // Text files
            'txt', 'csv',
            // Archives
            'zip', 'rar',
            // Web files
            'html', 'css', 'js', 'json', 'xml',
            // Audio/Video
            'mp3', 'mp4', 'avi', 'mov', 'webm',
        ]);
    }

    /**
     * Get custom validation messages
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'files.required' => trans('files.validation.files_required'),
            'files.array' => trans('files.validation.files_array'),
            'files.*.file.required' => trans('files.validation.file_required'),
            'files.*.file.file' => trans('files.validation.file_file'),
            'files.*.file.mimes' => trans('files.validation.file_mimes'),
            'files.*.file.max' => trans('files.validation.file_max'),
            'path.required' => trans('files.validation.path_required'),
            'path.string' => trans('files.validation.path_string'),
        ];
    }

    /**
     * Get custom attribute names
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'files' => 'failai',
            'files.*.file' => 'failas',
            'path' => 'kelias',
        ];
    }

    /**
     * Configure validator instance
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator): void {
            // Custom validation for file types with better error message
            if ($this->has('files')) {
                foreach ($this->files as $index => $fileContainer) {
                    if (isset($fileContainer['file'])) {
                        $file = $fileContainer['file'];
                        $extension = strtolower($file->getClientOriginalExtension());

                        if (! in_array($extension, self::getAllowedExtensions())) {
                            $validator->errors()->add(
                                "files.{$index}.file",
                                "Failas \"{$file->getClientOriginalName()}\" turi neleistiną formatą. ".
                                'Leidžiami formatai: '.implode(', ', self::getAllowedExtensions())
                            );
                        }
                    }
                }
            }
        });
    }

    /**
     * Get allowed extensions for frontend use
     */
    public static function getAllowedExtensions(): array
    {
        return [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'zip', 'rar',
            'html', 'css', 'js', 'json', 'xml',
            'mp3', 'mp4', 'avi', 'mov', 'webm',
        ];
    }
}
