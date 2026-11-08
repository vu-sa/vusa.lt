<?php

namespace App\Actions\Responsibilities;

use App\Models\Duty;
use App\Models\Role;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AuthorizeDutyAssignment
{
    public static function execute(User $actor, Duty $duty, string $userId): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        /** @var Collection<int, Role> $roles */
        $roles = $duty->roles()->with('permissions')->get();
        $managesResponsibilities = app(ModelAuthorizer::class)->allows($actor, 'roles.update.*');
        $hasGlobalRole = $roles->contains(fn (Role $role) => $role->name === config('permission.super_admin_role_name')
            || $role->permissions->contains(fn ($permission) => str_ends_with($permission->name, '.*')));

        if ($hasGlobalRole && ! $managesResponsibilities) {
            throw ValidationException::withMessages(['user_id' => __('dutiables.assign.global_responsibility')]);
        }

        if ((string) $actor->id === $userId && $roles->isNotEmpty()
            && $roles->diff(GrantableResponsibilities::for($actor))->isNotEmpty()) {
            throw ValidationException::withMessages(['user_id' => __('dutiables.assign.self_responsibility')]);
        }
    }
}
