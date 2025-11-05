<?php

namespace App\Http\Requests;

use App\Models\Problem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProblemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Problem::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'solution' => ['nullable', 'string'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'responsible_user_id' => ['nullable', 'string', 'exists:users,id'],
            'occurred_at' => ['required', 'date'],
            'resolved_at' => ['nullable', 'date', 'after_or_equal:occurred_at'],
            'status' => ['required', 'string', Rule::in(['open', 'in_progress', 'resolved'])],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:problem_categories,id'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The problem title is required.',
            'description.required' => 'The problem description is required.',
            'tenant_id.required' => 'The tenant is required.',
            'tenant_id.exists' => 'The selected tenant is invalid.',
            'occurred_at.required' => 'The occurred date is required.',
            'resolved_at.after_or_equal' => 'The resolved date must be after or equal to the occurred date.',
            'status.in' => 'The selected status is invalid.',
            'categories.*.exists' => 'One or more selected categories are invalid.',
        ];
    }
}
