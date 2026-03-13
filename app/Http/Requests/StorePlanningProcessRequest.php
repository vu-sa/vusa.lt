<?php

namespace App\Http\Requests;

use App\Models\PlanningProcess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanningProcessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', PlanningProcess::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'academic_year_start' => ['required', 'integer', 'min:2020', 'max:2100'],
            'moderator_user_id' => ['nullable', 'string', 'exists:users,id'],
        ];
    }
}
