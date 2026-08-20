<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProblemRequest extends FormRequest
{
    use ValidatesTenantScope;

    /**
     * The permission whose tenant scope constrains `tenant_id`. Store and Update override it
     * so each uses its own scope.
     */
    protected string $tenantScopePermission = 'problems.update.padalinys';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
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
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope($this->tenantScopePermission)],
            'responsible_user_id' => ['nullable', 'string', SoftDeleteRules::existsLive('users')],
            'occurred_at' => 'required|date',
            'resolved_at' => 'nullable|date|after_or_equal:occurred_at',
            'status' => 'required|string|in:open,in_progress,resolved',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:problem_categories,id',
            'institutions' => 'nullable|array',
            'institutions.*' => ['string', SoftDeleteRules::existsLive('institutions')],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'title.lt.required_without' => trans('problems.validation.title_required'),
            'title.en.required_without' => trans('problems.validation.title_required'),
            'description.lt.required_without' => trans('problems.validation.description_required'),
            'description.en.required_without' => trans('problems.validation.description_required'),
            'tenant_id.required' => trans('problems.validation.tenant_required'),
            'tenant_id.exists' => trans('problems.validation.tenant_exists'),
            'occurred_at.required' => trans('problems.validation.occurred_at_required'),
            'resolved_at.after_or_equal' => trans('problems.validation.resolved_at_after'),
            'status.in' => trans('problems.validation.status_in'),
            'categories.*.exists' => trans('problems.validation.categories_exist'),
            'institutions.*.exists' => trans('problems.validation.institutions_exist'),
        ];
    }
}
