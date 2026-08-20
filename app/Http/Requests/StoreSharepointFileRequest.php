<?php

namespace App\Http\Requests;

use App\Models\SharepointFile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSharepointFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SharepointFile::class);
    }

    /**
     * `fileable.type` is resolved through AllowedFileablesEnum in the controller, which is the
     * allowlist that decides which model class the id may point at.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|array',
            'file.uploadValue' => 'required|file',
            'file.typeValue' => 'required|string',
            'file.nameValue' => 'required|string',
            'file.datetimeValue' => 'required|numeric',
            'file.description0Value' => 'nullable|string',
            'fileable' => 'required|array',
            'fileable.id' => 'required',
            'fileable.type' => 'required|string',
        ];
    }
}
