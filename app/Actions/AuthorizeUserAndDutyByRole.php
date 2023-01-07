<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AuthorizeUserAndDutyByRole
{
    // The purpose of this is to authorize an user action not only
    // against the user but also against the duties that the user
    // has.

    // The authorization mechanism is role based. The user must
    // have a duty with a role that has the permission.

    private $user;
    private $duties;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
    
    protected function getDuties()
    {
        if (!isset($this->duties)) {
            $this->duties = $this->user->duties()->get(['id']);
        }

        return $this->duties;
    }

    public function checkAllRoleables(string $permission)
    {        
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
    public function check(string $permission)
    {
        return $this->checkAllRoleables($permission);
    }
    
}