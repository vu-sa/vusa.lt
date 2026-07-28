<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AtstovavimasTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_ids' => ['required', 'array', 'min:1', 'max:100'],
            'tenant_ids.*' => ['required', 'integer', 'distinct', 'exists:tenants,id'],
            'refresh' => ['nullable', 'boolean'],
        ];
    }
}
