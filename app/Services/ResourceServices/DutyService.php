<?php

namespace App\Services\ResourceServices;

use App\Models\Institution;
use App\Models\Meeting;
use App\Services\ModelAuthorizer;
use App\Settings\AtstovavimasSettings;
use Illuminate\Support\Collection;

class DutyService
{
    /**
     * Get institutions available for duty creation/editing (upserts).
     *
     * This uses permission-based filtering (duties.create.padalinys) to determine
     * which institutions a user can manage duties for.
     *
     * Note: This is intentionally different from getInstitutionsForDashboard() which
     * also includes institutions where the user is just a member (for viewing purposes).
     *
     * @see getInstitutionsForDashboard() for dashboard visibility logic
     */
    public static function getInstitutionsForUpserts(ModelAuthorizer $authorizer)
    {
        $user = request()->user();
        $authorizer = $authorizer->forUser($user);

        // Check for global access
        $hasGlobalAccess = $authorizer->check('duties.create.*');

        // Get tenant IDs where user can create duties
        $tenantIds = collect();
        if (! $hasGlobalAccess && $authorizer->check('duties.create.padalinys')) {
            $tenantIds = $authorizer->getTenants('duties.create.padalinys')->pluck('id');
        }

        return Institution::select('id', 'name', 'alias', 'tenant_id')
            ->when(! $hasGlobalAccess, function ($query) use ($tenantIds) {
                // Only show institutions from tenants where user has permission
                $query->whereIn('tenant_id', $tenantIds);
            })
            ->whereHas('tenant', function ($query) {
                $query->where('type', '!=', 'pkp');
            })
            ->with('tenant:id,shortname')
            ->get();
    }

    /**
     * Get institutions for the Atstovavimas dashboard.
     *
     * This uses permission-based visibility via AtstovavimasSettings::getVisibleTenantIds():
     *
     * - Super admins or users with institutions.read.*: See all institutions
     * - Users with institutions.read.padalinys: See institutions in authorized tenants
     * - Regular users: See only institutions they are directly assigned to via duties
     *
     * Note: This is intentionally different from getInstitutionsForUpserts() which uses
     * duties.create.padalinys permission for CRUD operations.
     *
     * @see AtstovavimasSettings::getVisibleTenantIds() for visibility logic
     * @see getInstitutionsForUpserts() for duty creation/editing
     */
    public static function getInstitutionsForDashboard(ModelAuthorizer $authorizer)
    {
        $user = request()->user();
        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $visibleTenantIds = $atstovavimasSettings->getVisibleTenantIds($user);
        $userInstitutionIds = $user->current_duties()
            ->pluck('institution_id')
            ->filter()
            ->unique();

        return Institution::select('id', 'name', 'alias', 'tenant_id')
            ->when($visibleTenantIds->isNotEmpty(), function ($query) use ($visibleTenantIds, $userInstitutionIds) {
                $query->where(function ($q) use ($visibleTenantIds, $userInstitutionIds) {
                    $q->whereIn('tenant_id', $visibleTenantIds);

                    if ($userInstitutionIds->isNotEmpty()) {
                        $q->orWhereIn('id', $userInstitutionIds);
                    }
                });
            }, function ($query) use ($userInstitutionIds) {
                $query->whereIn('id', $userInstitutionIds);
            })
            ->whereHas('tenant', function ($query) {
                $query->where('type', '!=', 'pkp');
            })
            ->with([
                'tenant:id,shortname',
                'types', // explicit since not auto-loaded
                'meetings:id,title,start_time',
                'meetings.agendaItems:id,meeting_id,title,student_vote,decision,student_benefit',
                // Load all users (including historical) for Gantt timeline display
                'duties.users:id,name,email,profile_photo_path',
                'duties.types:id,title,slug',
                'checkIns',
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query) {
                    $query->where('start_time', '>', now());
                },
            ])
            ->addSelect([
                'last_meeting_date' => Meeting::select('start_time')
                    ->join('institution_meeting', 'meetings.id', '=', 'institution_meeting.meeting_id')
                    ->whereColumn('institution_meeting.institution_id', 'institutions.id')
                    ->orderBy('start_time', 'desc')
                    ->limit(1),
            ])
            ->get();
    }

    /**
     * Get only user's directly assigned institutions for the dashboard.
     *
     * This is a lightweight version that only includes institutions where the user
     * has active duties. Used for the user's personal timeline tab.
     *
     * @see getInstitutionsForDashboard() for full access including coordinator tenants
     */
    public static function getUserInstitutionsForDashboard()
    {
        $user = request()->user();

        // Get user's directly assigned institution IDs
        $userInstitutionIds = $user->current_duties()
            ->pluck('institution_id')
            ->filter()
            ->unique();

        if ($userInstitutionIds->isEmpty()) {
            return collect();
        }

        return self::buildInstitutionQuery()
            ->whereIn('id', $userInstitutionIds)
            ->get();
    }

    /**
     * Get institutions for specific tenant IDs.
     *
     * This is used for lazy-loading tenant timeline data when the tenant tab
     * is opened or when the tenant filter changes.
     * Users with global visibility roles are treated as global access.
     *
     * @param  Collection|array  $tenantIds  The tenant IDs to load institutions for
     * @param  ModelAuthorizer  $authorizer  The authorizer to check access permissions
     */
    public static function getInstitutionsForTenants($tenantIds, ModelAuthorizer $authorizer)
    {
        $tenantIds = collect($tenantIds)->filter();

        if ($tenantIds->isEmpty()) {
            return collect();
        }

        $user = request()->user();
        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $visibleTenantIds = $atstovavimasSettings->getVisibleTenantIds($user);

        // Filter to only accessible tenants
        $accessibleTenantIds = $tenantIds->intersect($visibleTenantIds);

        if ($accessibleTenantIds->isEmpty()) {
            return collect();
        }

        return self::buildInstitutionQuery()
            ->whereIn('tenant_id', $accessibleTenantIds)
            ->get();
    }

    /**
     * Build the base query for dashboard institutions with all needed eager loading.
     */
    private static function buildInstitutionQuery()
    {
        return Institution::select('id', 'name', 'alias', 'tenant_id')
            ->whereHas('tenant', function ($query) {
                $query->where('type', '!=', 'pkp');
            })
            ->with([
                'tenant:id,shortname,type', // type is needed for cross-tenant scope matching in RelationshipService
                'types', // explicit since not auto-loaded
                'meetings:id,title,start_time',
                'meetings.agendaItems:id,meeting_id,title,student_vote,decision,student_benefit',
                // Load fileableFiles for has_report and has_protocol accessors (prevents N+1)
                'meetings.fileableFiles:id,fileable_id,fileable_type,file_type,deleted_externally_at',
                // Load all users (including historical) for Gantt timeline display
                'duties.users:id,name,email,profile_photo_path',
                'duties.types:id,title,slug',
                'checkIns',
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query) {
                    $query->where('start_time', '>', now());
                },
            ])
            ->addSelect([
                'last_meeting_date' => Meeting::select('start_time')
                    ->join('institution_meeting', 'meetings.id', '=', 'institution_meeting.meeting_id')
                    ->whereColumn('institution_meeting.institution_id', 'institutions.id')
                    ->orderBy('start_time', 'desc')
                    ->limit(1),
            ]);
    }
}
