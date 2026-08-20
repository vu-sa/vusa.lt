<?php

namespace App\Http\Requests;

use App\Enums\PermissionScopeEnum;
use App\Models\Permission;
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

        return [
            'create' => ['nullable', 'string', Rule::in($scopes)],
            'read' => ['nullable', 'string', Rule::in($scopes)],
            'update' => ['nullable', 'string', Rule::in($scopes)],
            'delete' => ['nullable', 'string', Rule::in($scopes)],
            'forceDelete' => ['nullable', 'string', Rule::in($scopes)],
        ];
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
