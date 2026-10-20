<?php

namespace App\Http\Requests;

use App\Enums\ResponsibilityScope;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\Role;
use App\Rules\SoftDeleteRules;
use App\Support\MorphMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Granting from the responsibility's own page: whoever may edit the responsibility may give it to any
 * duty, duty type or institution. A purpose's scope is required where the purpose has scopes.
 */
class StoreRoleGrantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->role());
    }

    protected function prepareForValidation(): void
    {
        abort_if($this->role()->isSuperAdmin(), 403, __('messages.role.not_assignable_to_duty'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $scopes = $this->role()->purpose?->allowedScopes() ?? [];
        $granteeTable = match ($this->input('grantee_type')) {
            MorphMap::alias(Duty::class) => 'duties',
            MorphMap::alias(DutyType::class) => 'duty_types',
            MorphMap::alias(Institution::class) => 'institutions',
            default => null,
        };

        return [
            'grantee_type' => ['required', Rule::in(array_map(MorphMap::alias(...), [Duty::class, DutyType::class, Institution::class]))],
            'grantee_id' => ['required', 'string', 'max:26', ...($granteeTable ? [SoftDeleteRules::existsLive($granteeTable)] : [])],
            'scope_type' => $scopes === []
                ? ['prohibited']
                : ['required', Rule::in(array_map(fn (ResponsibilityScope $scope) => $scope->value, $scopes))],
            'scope_id' => $scopes === []
                ? ['prohibited']
                : ['required', 'string', 'max:26', $this->scopeExists(...)],
        ];
    }

    public function scopeType(): ?ResponsibilityScope
    {
        return ResponsibilityScope::tryFrom((string) $this->validated('scope_type'));
    }

    public function role(): Role
    {
        return $this->route('role');
    }

    private function scopeExists(string $attribute, mixed $value, \Closure $fail): void
    {
        $scope = ResponsibilityScope::tryFrom((string) $this->input('scope_type'));

        if ($scope !== null && ! $scope->modelClass()::query()->whereKey($value)->exists()) {
            $fail(__('responsibilities.validation.target_out_of_reach'));
        }
    }
}
