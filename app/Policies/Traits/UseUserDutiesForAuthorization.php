<?php

namespace App\Policies\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

trait UseUserDutiesForAuthorization
{
    // The purpose of this is to authorize an user action not only
    // against the user but also against the duties that the user
    // has.

    // The authorization mechanism is role based. The user must
    // have a duty with a role that has the permission.

    private User $user;
    private Collection $duties;
    private Collection $permissableDuties;

    public function getPermissableDuties(): Collection
    {
        return $this->permissableDuties;
    }

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

        $this->permissableDuties = new Collection();

        // TODO: check, if cache invalidation works on this
        // return Cache::remember($permission . '-' . $this->user->id, 1800, function () use ($permission) {
            // check if user has permission
            if ($this->user->hasPermissionTo($permission)) {
                return true;
            }

            $this->getDuties();

            // check if at least one duty has permission
            // TODO: should check all duties and in the object save the duties that have the abiility
            foreach ($this->duties as $duty) {
                if ($duty->hasPermissionTo($permission)) {
                    $this->permissableDuties->push($duty);
                }
            }

            if ($this->permissableDuties->count() > 0) {
                return true;
            }

            return false;
            // }
        // );
    }

    // alias for checkAllRoleables
    public function check(string $permission): bool
    {
        return $this->checkAllRoleables($permission);
    }

    // if array is checked, then permissable IDs are reset
    // public function checkArray(array $permissions): bool
    // {
    //     foreach ($permissions as $permission) {
    //         if ($this->check($permission)) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    protected function getDuties(): Collection
    {
        if (!isset($this->duties)) {          
            $this->duties = $this->user->load('duties:id,institution_id', 'duties.institution:id')->duties;
        }

        return $this->duties;
    }
    
}