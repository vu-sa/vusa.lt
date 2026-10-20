<?php

namespace App\Http\Requests;

use App\Actions\Responsibilities\GrantableResponsibilities;
use App\Enums\ResponsibilityScope;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Role;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Granting a responsibility grants its permissions, so the actor must be able to edit the duty and
 * hand out that responsibility. A purpose's scope must be something the actor already manages.
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
        $scoped = $this->role()?->purpose?->isScoped() ?? false;

        return [
            'role_id' => ['required', 'string', Rule::exists('roles', 'id'), $this->grantable(...)],
            'scope_type' => $scoped
                ? ['required', Rule::enum(ResponsibilityScope::class), $this->scopeAllowed(...)]
                : ['prohibited'],
            'scope_id' => $scoped
                ? ['required', 'string', 'max:26', $this->targetWithinReach(...)]
                : ['prohibited'],
        ];
    }

    public function scopeType(): ?ResponsibilityScope
    {
        return ResponsibilityScope::tryFrom((string) $this->validated('scope_type'));
    }

    private function role(): ?Role
    {
        return Role::query()->find($this->input('role_id'));
    }

    private function grantable(string $attribute, mixed $value, Closure $fail): void
    {
        $role = $this->role();

        if ($role === null || $role->isSuperAdmin() || ! GrantableResponsibilities::allows($this->user(), $role)) {
            $fail(__('responsibilities.validation.not_grantable'));
        }
    }

    private function scopeAllowed(string $attribute, mixed $value, Closure $fail): void
    {
        $scope = ResponsibilityScope::tryFrom((string) $value);

        if ($scope !== null && ! in_array($scope, $this->role()?->purpose?->allowedScopes() ?? [], true)) {
            $fail(__('responsibilities.validation.scope_not_allowed'));
        }
    }

    private function targetWithinReach(string $attribute, mixed $value, Closure $fail): void
    {
        $allowed = match (ResponsibilityScope::tryFrom((string) $this->input('scope_type'))) {
            ResponsibilityScope::Tenant => in_array((string) $value, array_map(strval(...), $this->authorizedTenantIds('duties.update.padalinys')), true),
            ResponsibilityScope::Institution => ($institution = Institution::query()->find($value)) !== null
                && in_array((int) $institution->tenant_id, $this->authorizedTenantIds('duties.update.padalinys'), true),
            ResponsibilityScope::InstitutionType => ($type = InstitutionType::query()->find($value)) !== null
                && $this->user()->can('update', $type),
            null => true,
        };

        if (! $allowed) {
            $fail(__('responsibilities.validation.target_out_of_reach'));
        }
    }
}
