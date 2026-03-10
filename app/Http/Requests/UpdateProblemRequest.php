<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProblemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('problem'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title.lt' => 'required_without:title.en|nullable|string|max:255',
            'title.en' => 'required_without:title.lt|nullable|string|max:255',
            'description.lt' => 'required_without:description.en|nullable|string',
            'description.en' => 'required_without:description.lt|nullable|string',
            'solution.lt' => 'nullable|string',
            'solution.en' => 'nullable|string',
            'steps_taken.lt' => 'nullable|string',
            'steps_taken.en' => 'nullable|string',
            'tenant_id' => 'required|integer|exists:tenants,id',
            'responsible_user_id' => 'nullable|string|exists:users,id',
            'occurred_at' => 'required|date',
            'resolved_at' => 'nullable|date|after_or_equal:occurred_at',
            'status' => 'required|string|in:open,in_progress,resolved',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:problem_categories,id',
            'institutions' => 'nullable|array',
            'institutions.*' => 'string|exists:institutions,id',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.lt.required_without' => 'The problem title must be provided in at least one language.',
            'title.en.required_without' => 'The problem title must be provided in at least one language.',
            'description.lt.required_without' => 'The problem description must be provided in at least one language.',
            'description.en.required_without' => 'The problem description must be provided in at least one language.',
            'tenant_id.required' => 'The tenant is required.',
            'tenant_id.exists' => 'The selected tenant is invalid.',
            'occurred_at.required' => 'The occurred date is required.',
            'resolved_at.after_or_equal' => 'The resolved date must be after or equal to the occurred date.',
            'status.in' => 'The selected status is invalid.',
            'categories.*.exists' => 'One or more selected categories are invalid.',
            'institutions.*.exists' => 'One or more selected institutions are invalid.',
        ];
    }
}
