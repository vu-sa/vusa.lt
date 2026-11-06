<?php

namespace App\Observers;

use App\Actions\ForgetUserAccess;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\RoleType;
use App\Models\User;
use App\Support\MorphMap;

class RoleTypeObserver
{
    public function saved(RoleType $roleType): void
    {
        $role = $roleType->role;

        $type = $roleType->type;

        if ($type->model_type === MorphMap::alias(Duty::class)) {
            $duties = $type->duties;
        } else {
            return;
        }

        $role->duties()->syncWithoutDetaching($duties);

        ForgetUserAccess::execute(Dutiable::query()
            ->whereIn('duty_id', $duties->modelKeys())
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->distinct()
            ->pluck('dutiable_id'));
    }

    public function deleted(RoleType $roleType): void
    {
        $role = $roleType->role;

        $type = $roleType->type;

        if ($type->model_type === MorphMap::alias(Duty::class)) {
            $duties = $type->duties;
        } else {
            return;
        }

        $holderIds = Dutiable::query()
            ->whereIn('duty_id', $duties->modelKeys())
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->distinct()
            ->pluck('dutiable_id');

        $role->duties()->detach($duties);

        ForgetUserAccess::execute($holderIds);
    }
}
