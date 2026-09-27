<?php

namespace App\Http\Requests;

use App\Enums\Responsibility;
use App\Enums\ResponsibilityScope;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Type;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Assigning a responsibility changes who is notified and who reps are pointed to, so the target
 * must be something the actor may already manage: a padalinys or institution within their duty
 * scope, or a type they may edit.
 */
class StoreDutyResponsibilityRequest extends FormRequest
{
    use ValidatesTenantScope;

    public function authorize(): bool
    {
        /** @var Duty $duty */
        $duty = $this->route('duty');

        return $this->user()->can('update', $duty);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'responsibility' => ['required', Rule::enum(Responsibility::class)],
            'scope_type' => ['required', Rule::enum(ResponsibilityScope::class), $this->scopeAllowedForResponsibility(...)],
            'scope_id' => ['required', 'string', 'max:26', $this->targetWithinReach(...)],
        ];
    }

    public function responsibility(): Responsibility
    {
        return Responsibility::from($this->validated('responsibility'));
    }

    public function scopeType(): ResponsibilityScope
    {
        return ResponsibilityScope::from($this->validated('scope_type'));
    }

    private function scopeAllowedForResponsibility(string $attribute, mixed $value, Closure $fail): void
    {
        $responsibility = Responsibility::tryFrom((string) $this->input('responsibility'));
        $scope = ResponsibilityScope::tryFrom((string) $value);

        if ($responsibility !== null && $scope !== null && ! in_array($scope, $responsibility->allowedScopes(), true)) {
            $fail(__('responsibilities.validation.scope_not_allowed'));
        }
    }

    private function targetWithinReach(string $attribute, mixed $value, Closure $fail): void
    {
        $scope = ResponsibilityScope::tryFrom((string) $this->input('scope_type'));

        $allowed = match ($scope) {
            ResponsibilityScope::Tenant => in_array((string) $value, array_map(strval(...), $this->authorizedTenantIds('duties.update.padalinys')), true),
            ResponsibilityScope::Institution => ($institution = Institution::query()->find($value)) !== null
                && in_array((int) $institution->tenant_id, $this->authorizedTenantIds('duties.update.padalinys'), true),
            ResponsibilityScope::Type => ($type = Type::query()->forInstitutions()->find($value)) !== null
                && $this->user()->can('update', $type),
            null => true,
        };

        if (! $allowed) {
            $fail(__('responsibilities.validation.target_out_of_reach'));
        }
    }
}
