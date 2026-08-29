<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UserSearchRequest extends FormRequest
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
            'search' => 'required|string|min:2',
            // Required on purpose: the tenant scoping is derived from it, so a
            // forgotten parameter must fail loudly rather than silently scope
            // by some unrelated permission.
            'permission' => 'required|string',
            'scope' => 'nullable|in:tenant,all',
        ];
    }
}
