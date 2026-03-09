<?php

namespace App\Services\ResourceServices;

use App\Models\Duty;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UserDutyService
{
    /**
     * Sync duties for a user, respecting tenant-based permissions.
     *
     * @param  Collection  $existingDutyIds  The new set of duty IDs
     * @param  Collection  $currentDutyIds  The current duty IDs on the user
     * @param  User  $user  The user to update
     * @param  ModelAuthorizer  $authorizer  The authorizer instance
     */
    public static function syncDutiesForUser(
        Collection $existingDutyIds,
        Collection $currentDutyIds,
        User $user,
        ModelAuthorizer $authorizer
    ): void {
        $permissableTenants = self::getPermissableTenants($authorizer);

        $newDutyIds = $existingDutyIds->diff($currentDutyIds)->values();
        $removedDutyIds = $currentDutyIds->diff($existingDutyIds)->values();

        // Attach new duties
        foreach ($newDutyIds as $dutyId) {
            $duty = Duty::find($dutyId);

            if (! $duty || ! $permissableTenants->contains($duty->institution->tenant)) {
                continue;
            }

            $user->duties()->attach($duty, ['start_date' => now()->subDay()]);
        }

        // End removed duties (set end_date)
        foreach ($removedDutyIds as $dutyId) {
            $duty = Duty::find($dutyId);

            if (! $duty || ! $permissableTenants->contains($duty->institution->tenant)) {
                continue;
            }

            $user->duties()->updateExistingPivot($duty, ['end_date' => now()->subDay()]);
        }
    }

    /**
     * Get tenants with institutions and duties for form selection.
     *
     * @param  ModelAuthorizer  $authorizer  The authorizer instance
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getTenantsWithDutiesForForm(ModelAuthorizer $authorizer)
    {
        $user = Auth::user();

        if (! $authorizer->forUser($user)->checkAllRoleables('users.create.all')) {
            return Tenant::orderBy('shortname')
                ->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
                ->whereIn('id', User::find(Auth::id())->tenants->pluck('id'))
                ->get();
        }

        return Tenant::orderBy('shortname')
            ->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
            ->get();
    }

    /**
     * Get tenants the current user can manage based on permissions.
     *
     * @param  ModelAuthorizer  $authorizer  The authorizer instance
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPermissableTenants(ModelAuthorizer $authorizer)
    {
        $currentUser = User::find(Auth::id());

        if ($currentUser->isSuperAdmin()) {
            return Tenant::all();
        }

        return $authorizer->getTenants();
    }
}
