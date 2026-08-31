<?php

namespace App\Actions;

use App\Models\Meeting;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Everyone with oversight of a meeting — the people who should hear about it because of
 * the position they hold, as opposed to the followers who asked to.
 *
 * Collects users from four sources (de-duplicated):
 * 1. Institution managers - users with institution management permissions for meeting's institutions
 * 2. Tenant-level coordinators - users with tenant visibility roles (from AtstovavimasSettings)
 *    for the meeting's institution tenants
 * 3. Global overseers - users with global visibility roles (from AtstovavimasSettings)
 * 4. Institution administrators - people nominated to look after the institution for the
 *    term the meeting fell in ({@see GetInstitutionAdministrators})
 *
 * Deliberately not called "administrators": that word names source 4 alone, a checkable row
 * in `institution_administrators`, and using it for the union of all four read as if
 * nominating someone were what put a coordinator on this list. Callers want the whole
 * notification audience anyway — reach for {@see ResolveMeetingNotificationAudience}, which
 * adds the followers.
 */
class GetMeetingOverseers
{
    /**
     * @return Collection<int, User>
     */
    public static function execute(Meeting $meeting): Collection
    {
        $meeting->load(['institutions.tenant']);

        $settings = app(AtstovavimasSettings::class);
        $tenantIds = $meeting->institutions->pluck('tenant_id')->filter()->unique();

        $overseers = collect();

        // 1. Get institution managers
        foreach ($meeting->institutions as $institution) {
            $managers = GetInstitutionManagers::execute($institution);
            $overseers = $overseers->merge($managers);
        }

        // 2. Get users with tenant visibility roles for these tenants
        $overseers = $overseers->merge(self::getTenantCoordinators($settings, $tenantIds));

        // 3. Get users with global visibility roles
        $overseers = $overseers->merge(self::getGlobalOverseers($settings));

        // 4. People nominated for the term this meeting fell in
        $overseers = $overseers->merge(GetInstitutionAdministrators::forMeeting($meeting));

        // Return unique users by ID
        return $overseers->unique('id')->values();
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
            ->whereHas('current_duties', function (Builder $query) use ($tenantIds, $tenantRoleIds): void {
                $query->whereHas('institution', function (Builder $q) use ($tenantIds): void {
                    $q->whereIn('tenant_id', $tenantIds);
                })->whereHas('roles', function (Builder $q) use ($tenantRoleIds): void {
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
    protected static function getGlobalOverseers(AtstovavimasSettings $settings): Collection
    {
        $globalRoleIds = $settings->getGlobalVisibilityRoleIds();

        if ($globalRoleIds->isEmpty()) {
            return collect();
        }

        // Get users who have global visibility roles either directly or through duties
        $usersWithDirectRoles = User::query()
            ->whereHas('roles', function (Builder $query) use ($globalRoleIds): void {
                $query->whereIn('id', $globalRoleIds);
            })
            ->get();

        $usersWithDutyRoles = User::query()
            ->whereHas('current_duties', function (Builder $query) use ($globalRoleIds): void {
                $query->whereHas('roles', function (Builder $q) use ($globalRoleIds): void {
                    $q->whereIn('id', $globalRoleIds);
                });
            })
            ->get();

        return $usersWithDirectRoles->merge($usersWithDutyRoles);
    }
}
