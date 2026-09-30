<?php

namespace App\Http\Requests\Goals;

use App\Enums\GoalStatus;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Institution;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class GoalRequest extends FormRequest
{
    use ValidatesTenantScope;

    protected string $tenantScopePermission = 'goals.update.padalinys';

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title.lt' => 'required_without:title.en|nullable|string|max:255',
            'title.en' => 'required_without:title.lt|nullable|string|max:255',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'expected_result.lt' => 'nullable|string|max:2000',
            'expected_result.en' => 'nullable|string|max:2000',
            'evaluation.lt' => 'nullable|string',
            'evaluation.en' => 'nullable|string',
            'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')->where('goals_enabled', true), $this->tenantIdInAuthorizedScope($this->tenantScopePermission)],
            // Institution overrides belong to one body's terms; a padalinys plans by the shared ladder.
            'cadence_id' => ['nullable', 'string', Rule::exists('cadences', 'id')->whereNull('institution_id')],
            // A crafted payload must not make another padalinys' duty responsible for this goal.
            'responsible_duty_id' => ['nullable', 'string', SoftDeleteRules::existsLive('duties')->whereIn(
                'institution_id',
                Institution::query()->where('tenant_id', $this->integer('tenant_id'))->pluck('id')->all(),
            )],
            'status' => ['required', Rule::enum(GoalStatus::class)],
            'is_public' => 'boolean',
        ];
    }

    #[\Override]
    public function messages(): array
    {
        return [
            'title.lt.required_without' => trans('goals.validation.title_required'),
            'title.en.required_without' => trans('goals.validation.title_required'),
            'responsible_duty_id.exists' => trans('goals.validation.duty_in_tenant'),
        ];
    }
}
