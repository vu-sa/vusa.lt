<?php

namespace App\Services;

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
        $this->duties = new Collection();
        $this->permissableDuties = new Collection();
    }

    public function getPermissableDuties(): Collection
    {
        return $this->permissableDuties;
    }

    public function forUser(User $user): self
    {
        $this->user = $user;

        // ? when user is set, reset duties?
        $this->duties = new Collection();

        return $this;
    }

    public function checkAllRoleables(string $permission): bool
    {        
        $this->permissableDuties = new Collection();
        
        if ($this->user->hasRole(config('permission.super_admin_role_name'))) {
            $this->isAllScope = true;
            return true;
        }

        // TODO: check, if cache invalidation works on this
        // return Cache::remember($permission . '-' . $this->user->id, 1800, function () use ($permission) {
            // check if user has permission
            // TODO: causes a bug, if a person has a permission returned from user, then it doesn't assign global scope
            if ($this->user->hasPermissionTo($permission)) {
                return true;
            }

            $this->getDuties();

            // check if at least one duty has permission
            // TODO: should check all duties and in the object save the duties that have the abiility
            foreach ($this->duties as $duty) {
                if ($duty->hasPermissionTo($permission)) {
                    $this->permissableDuties->push($duty);
                    // check if $duty has permission to all, but delimit permission by period
                    // delimit $permission
                    $globalPermVariant = explode('.', $permission);
                    $globalPermVariant[2] = '*';
                    $globalPermVariant = implode('.', $globalPermVariant);
                    if ($duty->hasPermissionTo($globalPermVariant)) {
                        $this->isAllScope = true;
                    }
                }
            }
            // * Must be kept, in order to load all duties that are permissable
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

    protected function getDuties(): Collection
    {
        if ($this->duties->count() === 0) {          
            $this->duties = $this->user->load('duties:id,name,institution_id', 'duties.institution:id', 'duties.roles.permissions')->duties;
        }

        return $this->duties;
    }

    private function getPadaliniaiFromPermissableDuties(): Collection
    {
        $padaliniai = $this->permissableDuties->load('institution.padalinys')->pluck('institution.padalinys')->flatten(1)->unique('id')->values();

        return new Collection($padaliniai);
    }

    // alias
    public function getPadaliniai(): Collection
    {
        return $this->getPadaliniaiFromPermissableDuties();
    }
}