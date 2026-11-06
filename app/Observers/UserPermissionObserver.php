<?php

namespace App\Observers;

use App\Actions\ForgetUserAccess;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Model;

class UserPermissionObserver
{
    public function updated(Model $model): void
    {
        match (true) {
            $model instanceof User => ForgetUserAccess::execute([$model->id]),
            $model instanceof Duty => $this->updatedDuty($model),
            default => null,
        };
    }

    public function saved(Model $model): void
    {
        // A touch can leave updated_at unchanged within the same second.
        if ($model instanceof Role) {
            $this->changedRolePermissions($model);
        }
    }

    public function deleted(Model $model): void
    {
        match (true) {
            $model instanceof User => ForgetUserAccess::execute([$model->id]),
            $model instanceof Role => $this->changedRolePermissions($model),
            $model instanceof Duty => $this->updatedDuty($model),
            default => null,
        };
    }

    public function attachedRoleToUser(User $user, Role $role): void
    {
        ForgetUserAccess::execute([$user->id]);
    }

    public function detachedRoleFromUser(User $user, Role $role): void
    {
        ForgetUserAccess::execute([$user->id]);
    }

    public function attachedDutyToUser(User $user, Duty $duty): void
    {
        ForgetUserAccess::execute([$user->id]);
    }

    public function detachedDutyFromUser(User $user, Duty $duty): void
    {
        ForgetUserAccess::execute([$user->id]);
    }

    public function updatedDuty(Duty $duty): void
    {
        ForgetUserAccess::execute(Dutiable::query()
            ->whereIn('duty_id', [$duty->id])
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->distinct()
            ->pluck('dutiable_id'));
    }

    public function changedRolePermissions(Role $role): void
    {
        $holderIds = Dutiable::query()
            ->whereIn('duty_id', $role->duties()->select('duties.id'))
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->distinct()
            ->pluck('dutiable_id');

        ForgetUserAccess::execute($holderIds->merge($role->users()->pluck('users.id')));
    }
}
