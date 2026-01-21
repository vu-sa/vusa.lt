<?php

namespace App\Observers;

use App\Facades\Permission;
use App\Models\Duty;
use App\Models\Role;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\Typesense\TypesenseScopedKeyService;
use App\Settings\AtstovavimasSettings;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer to handle permission cache invalidation when relevant models change.
 *
 * This observer is registered for User, Role, Duty, and Permission models.
 * The updated/deleted methods accept Model to handle all registered types.
 */
class UserPermissionObserver
{
    /**
     * Clear user permission cache when roles or duties are changed.
     */
    public function invalidateUserCache($userId)
    {
        if (! $userId) {
            return;
        }

        Permission::resetCache($userId);
        AtstovavimasSettings::clearManagerCache($userId);
        InstitutionAccessService::invalidateForUser($userId);
        TypesenseScopedKeyService::invalidateForUser($userId);
    }

    /**
     * Handle the model "updated" event.
     *
     * Routes to appropriate handler based on model type.
     */
    public function updated(Model $model): void
    {
        match (true) {
            $model instanceof User => $this->invalidateUserCache($model->id),
            $model instanceof Role => $this->changedRolePermissions($model),
            $model instanceof Duty => $this->updatedDuty($model),
            default => null, // Permission model - no specific handler needed
        };
    }

    /**
     * Handle the model "deleted" event.
     *
     * Routes to appropriate handler based on model type.
     */
    public function deleted(Model $model): void
    {
        match (true) {
            $model instanceof User => $this->invalidateUserCache($model->id),
            $model instanceof Role => $this->changedRolePermissions($model),
            $model instanceof Duty => $this->updatedDuty($model),
            default => null, // Permission model - no specific handler needed
        };
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

        if (! empty($dutyIds)) {
            Duty::whereIn('id', $dutyIds)->get()->each(function ($duty) {
                $duty->users->each(function ($user) {
                    $this->invalidateUserCache($user->id);
                });
            });
        }
    }
}
