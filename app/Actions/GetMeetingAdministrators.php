<?php

namespace App\Actions;

use App\Models\Meeting;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Get all administrators who should be notified about a meeting.
 *
 * Collects users from three sources (de-duplicated):
 * 1. Institution managers - users with institution management permissions for meeting's institutions
 * 2. Tenant-level coordinators - users with tenant visibility roles (from AtstovavimasSettings)
 *    for the meeting's institution tenants
 * 3. Global administrators - users with global visibility roles (from AtstovavimasSettings)
 */
class GetMeetingAdministrators
{
    /**
     * Execute the action to get unique administrators for a meeting.
     *
     * @return Collection<int, User>
     */
    public static function execute(Meeting $meeting): Collection
    {
        $meeting->load(['institutions.tenant']);

        $settings = app(AtstovavimasSettings::class);
        $tenantIds = $meeting->institutions->pluck('tenant_id')->filter()->unique();

        $administrators = collect();

        // 1. Get institution managers
        foreach ($meeting->institutions as $institution) {
            $managers = GetInstitutionManagers::execute($institution);
            $administrators = $administrators->merge($managers);
        }

        // 2. Get users with tenant visibility roles for these tenants
        $tenantCoordinators = self::getTenantCoordinators($settings, $tenantIds);
        $administrators = $administrators->merge($tenantCoordinators);

        // 3. Get users with global visibility roles
        $globalAdmins = self::getGlobalAdministrators($settings);
        $administrators = $administrators->merge($globalAdmins);

        // Return unique users by ID
        return $administrators->unique('id')->values();
    }

    /**
     * Get users who have tenant visibility roles for the given tenants.
     *
     * @param  Collection<int, int>  $tenantIds
     * @return Collection<int, User>
     */
    protected static function getTenantCoordinators(AtstovavimasSettings $settings, Collection $tenantIds): Collection
    {
        $tenantRoleIds = $settings->getTenantVisibilityRoleIds();

        if ($tenantRoleIds->isEmpty() || $tenantIds->isEmpty()) {
            return collect();
        }

        // Get users who have current duties with tenant visibility roles
        // in institutions that belong to the meeting's tenants
        return User::query()
            ->whereHas('current_duties', function (Builder $query) use ($tenantIds, $tenantRoleIds) {
                $query->whereHas('institution', function (Builder $q) use ($tenantIds) {
                    $q->whereIn('tenant_id', $tenantIds);
                })->whereHas('roles', function (Builder $q) use ($tenantRoleIds) {
                    $q->whereIn('id', $tenantRoleIds);
                });
            })
            ->get();
    }

    /**
     * Get users who have global visibility roles.
     *
     * @return Collection<int, User>
     */
    protected static function getGlobalAdministrators(AtstovavimasSettings $settings): Collection
    {
        $globalRoleIds = $settings->getGlobalVisibilityRoleIds();

        if ($globalRoleIds->isEmpty()) {
            return collect();
        }

        // Get users who have global visibility roles either directly or through duties
        $usersWithDirectRoles = User::query()
            ->whereHas('roles', function (Builder $query) use ($globalRoleIds) {
                $query->whereIn('id', $globalRoleIds);
            })
            ->get();

        $usersWithDutyRoles = User::query()
            ->whereHas('current_duties', function (Builder $query) use ($globalRoleIds) {
                $query->whereHas('roles', function (Builder $q) use ($globalRoleIds) {
                    $q->whereIn('id', $globalRoleIds);
                });
            })
            ->get();

        return $usersWithDirectRoles->merge($usersWithDutyRoles);
    }
}
