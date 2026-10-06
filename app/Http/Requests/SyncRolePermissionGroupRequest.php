<?php

namespace App\Http\Requests;

use App\Enums\PermissionScopeEnum;
use App\Models\Permission;
use App\Support\Permissions\BaselineAccess;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SyncRolePermissionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('role'));
    }

    /**
     * {model} is a free route segment. Left unchecked, a value of '%' made the controller's
     * LIKE match every permission while matching none of the requested ones, so the diff
     * detached the role's entire permission set.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        abort_unless(in_array($this->route('model'), self::permissionResources(), true), 404);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $scopes = array_map(fn (PermissionScopeEnum $scope) => $scope->label(), PermissionScopeEnum::cases());

        // A permission that does not exist used to be dropped silently, so the form claimed a save
        // it never made. A retired one gets its own reason: every member already has it.
        $grantable = function (string $attribute, mixed $value, Closure $fail): void {
            $permission = $this->route('model').'.'.$attribute.'.'.$value;

            if (BaselineAccess::isRetired($permission)) {
                $fail(__('validation.permission_is_baseline'));
            } elseif (! Permission::query()->where('name', $permission)->exists()) {
                $fail(__('validation.permission_not_available'));
            }
        };

        return collect(['create', 'read', 'update', 'delete', 'forceDelete'])
            ->mapWithKeys(fn (string $ability): array => [$ability => ['nullable', 'string', Rule::in($scopes), $grantable]])
            ->all();
    }

    /**
     * The resource prefixes that actually exist in the permission table (e.g. 'news',
     * 'duties'). Derived from the seeded permissions rather than a hand-kept list, so a newly
     * seeded resource is accepted the moment it exists.
     *
     * @return array<int, string>
     */
    public static function permissionResources(): array
    {
        return Permission::query()
            ->pluck('name')
            ->map(fn (string $name) => Str::before($name, '.'))
            ->unique()
            ->values()
            ->all();
    }
}
