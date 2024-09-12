<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

// use Illuminate\Support\Facades\Cache;

class ModelAuthorizer
{
    // The purpose of this is to authorize an user action not only
    // against the user but also against the duties that the user
    // has.

    // The authorization mechanism is role based. The user must
    // have a duty with a role that has the permission.

    // TODO: cache somehow against duty-permission? user-ability-modeltype?

    public User $user;
    public Collection $duties;
    public Collection $permissableDuties;
    public bool $isAllScope = false;

    public function __construct()
    {
        $this->duties = new Collection;
        $this->permissableDuties = new Collection;
    }

    public function getPermissableDuties(): Collection
    {
        return $this->permissableDuties;
    }

    public function forUser(User $user): self
    {
        if (!isset($this->user) || $this->user->id !== $user->id) {
            $this->user = $user;
            $this->duties = new Collection;
        }
    
        // check if user is different, if so, reset duties
        if ($this->user && $this->user->id !== $user->id) {
            $this->duties = new Collection;
        }

        return $this;
    }

/**
     * Check all roles for the given permission.
     *
     * @param string $permission
     * @return bool
     */
    public function checkAllRoleables(string $permission): bool
    {
        $this->permissableDuties = new Collection();

        if ($this->user->hasRole(config('permission.super_admin_role_name'))) {
            $this->isAllScope = true;
            return true;
        }

        if ($this->user->hasPermissionTo($permission)) {
            return true;
        }

        $this->loadDuties();

        foreach ($this->duties as $duty) {
            if ($duty->hasPermissionTo($permission)) {
                $this->permissableDuties->push($duty);
                if ($this->hasGlobalPermission($duty, $permission)) {
                    $this->isAllScope = true;
                }
            }
        }

        return $this->permissableDuties->isNotEmpty();
    }

    // alias for checkAllRoleables
    public function check(string $permission): bool
    {
        return $this->checkAllRoleables($permission);
    }

    protected function loadDuties(): Collection
    {
        if ($this->duties->isEmpty()) {
            $this->duties = $this->user->load('current_duties:id,name,institution_id', 'current_duties.institution:id', 'current_duties.roles.permissions')->current_duties;
        }

        return $this->duties;
    }

    /**
     * Get tenants from permissible duties.
     *
     * @return Collection<Tenant>
     */
    public function getTenants(): Collection
    {
        $tenants = $this->isAllScope 
            ? Tenant::all() 
            : $this->permissableDuties->load('institution.tenant')->pluck('institution.tenant')->flatten(1)->unique('id')->values();

        return new Collection($tenants);
    }

    /**
     * Check if the duty has a global permission.
     *
     * @param $duty
     * @param string $permission
     * @return bool
     */
    protected function hasGlobalPermission($duty, string $permission): bool
    {
        $globalPermVariant = explode('.', $permission);
        $globalPermVariant[2] = '*';
        $globalPermVariant = implode('.', $globalPermVariant);

        return $duty->hasPermissionTo($globalPermVariant);
    }
}
