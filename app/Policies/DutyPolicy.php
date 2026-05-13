<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\Duty;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Str;

class DutyPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::DUTY()->label);
    }

    /**
     * Whether the user can manage (add/edit/remove) people on this duty.
     *
     * Owning-tenant admins pass via the normal update check. Non-owning tenant
     * admins pass if their tenant appears in $duty->assignableTenants.
     * When $targetUser is provided, the cross-tenant path additionally requires
     * that user to belong to the admin's assignable tenant.
     */
    public function managePeople(User $user, Duty $duty, ?User $targetUser = null): bool
    {
        // Owning-tenant admin: full control.
        if ($this->update($user, $duty)) {
            return true;
        }

        // Cross-tenant: must hold duties.update.padalinys for a tenant that
        // appears in the duty's assignableTenants list.
        $authorizer = app(ModelAuthorizer::class)->forUser($user);

        if (! $authorizer->check('duties.update.padalinys')) {
            return false;
        }

        $duty->loadMissing('assignableTenants');

        $adminTenantIds = $authorizer->getTenants('duties.update.padalinys')->pluck('id');
        $assignableTenantIds = $duty->assignableTenants->pluck('id');

        $sharedTenantIds = $adminTenantIds->intersect($assignableTenantIds);

        if ($sharedTenantIds->isEmpty()) {
            return false;
        }

        // If a target user is given, they must belong to one of the shared tenants.
        if ($targetUser !== null) {
            $targetTenantIds = $targetUser->tenants()->pluck('tenants.id');

            return $targetTenantIds->intersect($sharedTenantIds)->isNotEmpty();
        }

        return true;
    }
}
