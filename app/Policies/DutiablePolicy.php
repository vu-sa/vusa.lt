<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;

class DutiablePolicy extends ModelPolicy
{
    public function manageDutiable(User $user, Dutiable $dutiable): bool
    {
        $duty = $dutiable->duty;
        if ($duty === null) {
            return false;
        }

        $policy = app(DutyPolicy::class);
        if ($policy->update($user, $duty)) {
            return true;
        }

        return $dutiable->tenant_id !== null
            && $policy->sharedAssignableTenantIds($user, $duty)->contains($dutiable->tenant_id)
            && $dutiable->user?->current_or_upcoming_tenants()->where('tenants.id', $dutiable->tenant_id)->exists();
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
