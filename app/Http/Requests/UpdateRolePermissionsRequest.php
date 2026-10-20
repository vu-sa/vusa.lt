<?php

namespace App\Http\Requests;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions\BaselineAccess;
use App\Support\Permissions\PermissionCatalog;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The whole permission set at once, as the editor shows it. Names outside the catalog are kept on
 * the role untouched, so they never appear here.
 */
class UpdateRolePermissionsRequest extends FormRequest
{
    /** What lets someone come back and undo the save. */
    private const array SELF_MANAGEMENT = ['roles.read.*', 'roles.update.*'];

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->role());
    }

    protected function prepareForValidation(): void
    {
        abort_if($this->role()->isSuperAdmin(), 403, __('messages.role.not_editable'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct', $this->grantable(...)],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $user = $this->user();
            $requested = (array) $this->input('permissions', []);
            $dropped = array_diff(self::SELF_MANAGEMENT, $requested);

            if ($user->isSuperAdmin() || $dropped === [] || ! $this->actorHoldsRole()) {
                return;
            }

            $role = $this->role();

            foreach ($dropped as $permission) {
                $heldElsewhere = $user->current_duties()
                    ->whereHas('roles', fn ($roles) => $roles->whereKeyNot($role->id)
                        ->whereHas('permissions', fn ($permissions) => $permissions->where('name', $permission)))
                    ->exists();

                if ($role->hasPermissionTo($permission) && ! $heldElsewhere) {
                    $validator->errors()->add('permissions', __('responsibilities.validation.self_lockout'));

                    return;
                }
            }
        }];
    }

    public function role(): Role
    {
        return $this->route('role');
    }

    private function actorHoldsRole(): bool
    {
        return $this->user()->current_duties()->whereHas('roles', fn ($roles) => $roles->whereKey($this->role()->id))->exists();
    }

    private function grantable(string $attribute, mixed $value, Closure $fail): void
    {
        $name = (string) $value;

        if (BaselineAccess::isRetired($name)) {
            $fail(__('validation.permission_is_baseline', ['permission' => $name]));

            return;
        }

        if (! PermissionCatalog::isGrantable($name) || ! Permission::query()->where('name', $name)->exists()) {
            $fail(__('validation.permission_not_available', ['permission' => $name]));
        }
    }
}
