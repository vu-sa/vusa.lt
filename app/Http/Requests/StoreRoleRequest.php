<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The key (`name`) is generated from the Lithuanian title, so people only ever type the title.
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.lt' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.lt' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'name' => ['prohibited'],
        ];
    }
}
