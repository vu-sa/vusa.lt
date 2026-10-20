<?php

namespace App\Http\Requests;

use App\Enums\ResponsibilityScope;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Rules\SoftDeleteRules;
use App\Support\MorphMap;
use App\Support\Permissions\TypeGrantablePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Granting from the responsibility's own page: whoever may edit the responsibility may give it to any
 * duty, duty type or institution. A purpose's scope is required where the purpose has scopes (a type may
 * cover each duty's own padalinys, an institution only its own), and a duty type only gets responsibilities
 * TypeGrantablePermissions allows.
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
        $scopes = array_map(fn (ResponsibilityScope $scope) => $scope->value, $this->role()->purpose?->allowedScopes() ?? []);

        if (in_array(ResponsibilityScope::Tenant->value, $scopes, true)) {
            $scopes = match ($this->input('grantee_type')) {
                MorphMap::alias(DutyType::class) => [...$scopes, ResponsibilityGrant::OWN_TENANT],
                // An institution's duties share its padalinys; a fixed one would only repeat or contradict it.
                MorphMap::alias(Institution::class) => array_map(fn (string $scope) => $scope === ResponsibilityScope::Tenant->value ? ResponsibilityGrant::OWN_TENANT : $scope, $scopes),
                default => $scopes,
            };
        }
        $granteeTable = match ($this->input('grantee_type')) {
            MorphMap::alias(Duty::class) => 'duties',
            MorphMap::alias(DutyType::class) => 'duty_types',
            MorphMap::alias(Institution::class) => 'institutions',
            default => null,
        };

        return [
            'grantee_type' => ['required', Rule::in(array_map(MorphMap::alias(...), [Duty::class, DutyType::class, Institution::class]))],
            'grantee_id' => ['required', 'string', 'max:26', ...($granteeTable ? [SoftDeleteRules::existsLive($granteeTable)] : []), $this->grantableToType(...)],
            'scope_type' => $scopes === []
                ? ['prohibited']
                : ['required', Rule::in($scopes)],
            'scope_id' => $scopes === [] || $this->coversOwnTenant()
                ? ['prohibited']
                : ['required', 'string', 'max:26', $this->scopeExists(...)],
        ];
    }

    /**
     * The scope columns to store: an own-padalinys grant is a tenant scope without an id.
     *
     * @return array{scope_type: string|null, scope_id: string|null}
     */
    public function scopeColumns(): array
    {
        if ($this->coversOwnTenant()) {
            return ['scope_type' => ResponsibilityScope::Tenant->value, 'scope_id' => null];
        }

        $scopeType = ResponsibilityScope::tryFrom((string) $this->validated('scope_type'));

        return ['scope_type' => $scopeType?->value, 'scope_id' => $scopeType ? (string) $this->validated('scope_id') : null];
    }

    private function coversOwnTenant(): bool
    {
        return $this->input('scope_type') === ResponsibilityGrant::OWN_TENANT;
    }

    public function role(): Role
    {
        return $this->route('role');
    }

    private function grantableToType(string $attribute, mixed $value, \Closure $fail): void
    {
        if ($this->input('grantee_type') !== MorphMap::alias(DutyType::class)) {
            return;
        }

        $violations = TypeGrantablePermissions::violationsOf($this->role());

        if ($violations !== []) {
            $fail(__('responsibilities.validation.not_type_grantable', ['permissions' => implode(', ', $violations)]));
        }
    }

    private function scopeExists(string $attribute, mixed $value, \Closure $fail): void
    {
        $scope = ResponsibilityScope::tryFrom((string) $this->input('scope_type'));

        if ($scope !== null && ! $scope->modelClass()::query()->whereKey($value)->exists()) {
            $fail(__('responsibilities.validation.target_out_of_reach'));
        }
    }
}
