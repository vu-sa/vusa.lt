<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFilesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /* public function authorize(): bool */
    /* { */
    /*    return false; */
    /* } */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
    public function messages(): array
    {
        return [
            'files.required' => 'Nepasirinktas nei vienas failas įkėlimui.',
            'files.array' => 'Neteisingas failų formatas.',
            'files.*.file.required' => 'Failas yra privalomas.',
            'files.*.file.file' => 'Įkeltas elementas nėra failas.',
            'files.*.file.mimes' => 'Failas turi būti vienas iš šių formatų: :values',
            'files.*.file.max' => 'Failas negali būti didesnis nei 50MB.',
            'path.required' => 'Katalogo kelias yra privalomas.',
            'path.string' => 'Katalogo kelias turi būti tekstas.',
        ];
    }

    /**
     * Get custom attribute names
     */
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
        $validator->after(function ($validator) {
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
