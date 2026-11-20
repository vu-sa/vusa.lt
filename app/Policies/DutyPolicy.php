<?php

namespace App\Policies;

use App\Models\Duty;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;

class DutyPolicy extends ModelPolicy
{
    /**
     * Duty holders retain read access to duties they once held.
     *
     * @param  Duty  $duty
     */
    #[\Override]
    public function view(User $user, Model $duty): bool
    {
        return $duty->users()->whereKey($user->getKey())->exists() || parent::view($user, $duty);
    }

    /** Merging rewrites assignment history and permanently deletes duties, so only super admins may. */
    public function merge(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Cross-tenant admins can manage seats for their assignable tenant only.
     * When a target user is provided, they must have a non-ended term in that tenant.
     */
    public function managePeople(User $user, Duty $duty, ?User $targetUser = null): bool
    {
        // Owning-tenant admin: full control.
        if ($this->update($user, $duty)) {
            return true;
        }

        // Cross-tenant: must hold duties.update.padalinys for a tenant that
        // appears in the duty's assignableTenants list.
        $adminTenantIds = app(ModelAuthorizer::class)
            ->tenants($user, 'duties.update.padalinys')->pluck('id');

        if ($adminTenantIds->isEmpty()) {
            return false;
        }

        $duty->loadMissing('assignableTenants');
        $assignableTenantIds = $duty->assignableTenants->pluck('id');

        $sharedTenantIds = $adminTenantIds->intersect($assignableTenantIds);

        if ($sharedTenantIds->isEmpty()) {
            return false;
        }

        if ($targetUser !== null) {
            $targetTenantIds = $targetUser->current_or_upcoming_tenants()->pluck('tenants.id');

            return $targetTenantIds->intersect($sharedTenantIds)->isNotEmpty();
        }

        return true;
    }
}
