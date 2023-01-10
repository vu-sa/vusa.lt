<?php

namespace App\Policies\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

trait UseUserDutiesForAuthorization
{
    // The purpose of this is to authorize an user action not only
    // against the user but also against the duties that the user
    // has.

    // The authorization mechanism is role based. The user must
    // have a duty with a role that has the permission.

    private $user;
    private $duties;

    public function forUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function checkAllRoleables(string $permission): bool
    {        
        if ($this->user->hasRole(config('permission.super_admin_role_name'))) {
            return true;
        }

        // TODO: check, if cache invalidation works on this
        return Cache::remember($permission . '-' . $this->user->id, 1800, function () use ($permission) {
            // check if user has permission
            if ($this->user->hasPermissionTo($permission)) {
                return true;
            }

            $this->getDuties();

            // check if at least one duty has permission
            foreach ($this->duties as $duty) {
                if ($duty->hasPermissionTo($permission)) {
                    return true;
                }
            }

            return false;
            }
        );
    }

    // alias for checkAllRoleables
    public function check(string $permission): bool
    {
        return $this->checkAllRoleables($permission);
    }

    public function checkArray(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->check($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function getDuties(): Collection
    {
        if (!isset($this->duties)) {          
            $this->duties = $this->user->load('duties:id')->duties;
        }

        return $this->duties;
    }
    
}