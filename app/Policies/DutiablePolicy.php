<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;

class DutiablePolicy extends ModelPolicy
{
    /**
     * Whether the user can manage (edit/delete) this specific Dutiable row.
     *
     * Delegates to DutyPolicy::managePeople, which handles both owning-tenant
     * and cross-tenant assignable-tenant authorization.
     */
    public function manageDutiable(User $user, Dutiable $dutiable): bool
    {
        $duty = $dutiable->duty;

        if (! $duty) {
            return false;
        }

        $targetUser = $dutiable->dutiable_type === MorphMap::alias(User::class)
            ? $dutiable->user
            : null;

        return app(DutyPolicy::class)->managePeople($user, $duty, $targetUser);
    }

    /**
     * Whether the user may decide which padalinys quota a seat on this duty counts toward.
     * Checked against the duty, not a seat, so it also answers before the seat exists.
     */
    public function assignQuota(User $user, Duty $duty): bool
    {
        return $this->commonChecker($user, $duty, CRUDEnum::UPDATE->label(), $this->pluralModelName);
    }
}
