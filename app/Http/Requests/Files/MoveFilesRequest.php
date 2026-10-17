<?php

namespace App\Http\Requests\Files;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Each source directory and the destination are authorized in the controller after normalisation. */
class MoveFilesRequest extends FormRequest
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
            'paths' => 'required|array|min:1|max:50',
            'paths.*' => 'required|string',
            'destination' => 'required|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paths.required' => __('files.validation.move_paths_required'),
            'paths.min' => __('files.validation.move_paths_required'),
            'paths.max' => __('files.validation.paths_max'),
            'destination.required' => __('files.validation.path_required'),
        ];
    }
}
