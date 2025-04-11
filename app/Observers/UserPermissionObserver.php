<?php

namespace App\Observers;

use App\Facades\Permission;
use App\Models\Duty;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Observer to handle permission cache invalidation when relevant models change.
 */
class UserPermissionObserver
{
    /**
     * Clear user permission cache when roles or duties are changed.
     */
    public function invalidateUserCache($userId)
    {
        if (!$userId) {
            return;
        }
        
        Permission::resetCache($userId);
    }
    
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $this->invalidateUserCache($user->id);
    }
    
    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->invalidateUserCache($user->id);
    }
    
    /**
     * Handle role assignment to user
     */
    public function attachedRoleToUser(User $user, Role $role): void
    {
        $this->invalidateUserCache($user->id);
    }
    
    /**
     * Handle role removal from user
     */
    public function detachedRoleFromUser(User $user, Role $role): void
    {
        $this->invalidateUserCache($user->id);
    }
    
    /**
     * Handle duty assignment to user
     */
    public function attachedDutyToUser(User $user, Duty $duty): void
    {
        $this->invalidateUserCache($user->id);
    }
    
    /**
     * Handle duty removal from user
     */
    public function detachedDutyFromUser(User $user, Duty $duty): void
    {
        $this->invalidateUserCache($user->id);
    }
    
    /**
     * Handle duty update
     */
    public function updatedDuty(Duty $duty): void
    {
        // Clear cache for all users with this duty
        $duty->users->each(function ($user) {
            $this->invalidateUserCache($user->id);
        });
    }
    
    /**
     * Handle permission change in a role
     */
    public function changedRolePermissions(Role $role): void
    {
        // Clear cache for all users with this role
        $role->users->each(function ($user) {
            $this->invalidateUserCache($user->id);
        });
        
        // Also clear cache for users with duties that have this role
        $dutyIds = $role->duties()->pluck('duties.id')->toArray();
        
        if (!empty($dutyIds)) {
            Duty::whereIn('id', $dutyIds)->get()->each(function ($duty) {
                $duty->users->each(function ($user) {
                    $this->invalidateUserCache($user->id);
                });
            });
        }
    }
}