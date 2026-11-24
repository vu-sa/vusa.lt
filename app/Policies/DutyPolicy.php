<?php

namespace App\Policies;

use App\Models\Duty;
use App\Models\User;
use App\Services\DutyAccessService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DutyPolicy extends ModelPolicy
{
    /**
     * Allow scoped readers and current or former duty holders to open the record.
     *
     * @param  Duty  $duty
     */
    #[\Override]
    public function view(User $user, Model $duty): bool
    {
        return app(DutyAccessService::class)->allowsRead($user, $duty);
    }

    #[\Override]
    protected function tenantsFor(Model $model, string $tenantRelation): Collection
    {
        if ($model instanceof Duty && $model->relationLoaded('institution')) {
            $institution = $model->institution;

            if ($institution === null || $institution->trashed()) {
                return new Collection;
            }

            // A projection without tenant_id leaves the tenant relation empty rather than absent.
            if ($institution->relationLoaded('tenant') && array_key_exists('tenant_id', $institution->getAttributes())) {
                $tenant = $institution->tenant;

                return new Collection($tenant === null ? [] : [$tenant]);
            }
        }

        return parent::tenantsFor($model, $tenantRelation);
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

        $sharedTenantIds = $this->sharedAssignableTenantIds($user, $duty);

        if ($sharedTenantIds->isEmpty()) {
            return false;
        }

        if ($targetUser !== null) {
            $targetTenantIds = $targetUser->current_or_upcoming_tenants()->pluck('tenants.id');

            return $targetTenantIds->intersect($sharedTenantIds)->isNotEmpty();
        }

        return true;
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    public function sharedAssignableTenantIds(User $user, Duty $duty): \Illuminate\Support\Collection
    {
        $adminTenantIds = $this->authorizer->tenants($user, 'duties.update.padalinys')->pluck('id');

        $duty->loadMissing('assignableTenants');

        return $duty->assignableTenants->pluck('id')->intersect($adminTenantIds);
    }
}
