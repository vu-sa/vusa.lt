<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TrackRecentPageRequest extends FormRequest
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
            'route' => 'required_without:clear|nullable|string',
            'params' => 'nullable|array',
            'title' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:2048',
            'clear' => 'nullable|boolean',
        ];
    }
}
