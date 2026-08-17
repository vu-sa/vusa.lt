<?php

namespace App\Http\Requests;

use App\Models\SharepointFile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateSharepointFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SharepointFile::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'path' => 'required|string',
            'name' => 'required|string|max:255',
        ];
    }
}
