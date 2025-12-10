<?php

namespace App\Services\ResourceServices;

use App\Models\Institution;
use App\Models\Meeting;
use App\Services\ModelAuthorizer;
use App\Settings\AtstovavimasSettings;

class DutyService
{
    /**
     * Get institutions available for duty creation/editing (upserts).
     *
     * This uses permission-based filtering (duties.create.all) because creating duties
     * is a CRUD operation that should be governed by permissions, not visibility rules.
     *
     * Note: This is intentionally different from getInstitutionsForDashboard() which uses
     * coordinator role-based visibility (configurable business rule via AtstovavimasSettings).
     *
     * @see getInstitutionsForDashboard() for dashboard visibility logic
     */
    public static function getInstitutionsForUpserts(ModelAuthorizer $authorizer)
    {
        return Institution::select('id', 'name', 'alias', 'tenant_id')
            ->when(! $authorizer->forUser(request()->user())->checkAllRoleables('duties.create.all'),
                function ($query) {
                    $query->whereIn('tenant_id', auth()->user()->tenants->pluck('id'));
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
     * This uses coordinator role-based visibility (configurable via AtstovavimasSettings)
     * rather than permission-based filtering. The logic is:
     *
     * - Super admins: See all institutions
     * - Users with coordinator role in a tenant: See all institutions in that tenant
     * - Regular users: See only institutions they are directly assigned to via duties
     *
     * Note: This is intentionally different from getInstitutionsForUpserts() which uses
     * permission-based access for CRUD operations.
     *
     * @see AtstovavimasSettings for coordinator role configuration
     * @see getInstitutionsForUpserts() for duty creation/editing
     */
    public static function getInstitutionsForDashboard(ModelAuthorizer $authorizer)
    {
        $user = request()->user();
        $hasGlobalAccess = $authorizer->forUser($user)->checkAllRoleables('duties.create.all');

        // Get tenant IDs where user has coordinator access
        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $coordinatorTenantIds = $atstovavimasSettings->getCoordinatorTenantIds($user);

        return Institution::select('id', 'name', 'alias', 'tenant_id')
            ->when(! $hasGlobalAccess, function ($query) use ($user, $coordinatorTenantIds) {
                // Get user's directly assigned institution IDs
                $userInstitutionIds = $user->current_duties()
                    ->pluck('institution_id')
                    ->filter()
                    ->unique();

                if ($coordinatorTenantIds->isNotEmpty()) {
                    // User has coordinator access to some tenants
                    // Show: all institutions from coordinator tenants + directly assigned institutions from other tenants
                    $query->where(function ($q) use ($coordinatorTenantIds, $userInstitutionIds) {
                        $q->whereIn('tenant_id', $coordinatorTenantIds)
                          ->orWhereIn('id', $userInstitutionIds);
                    });
                } else {
                    // Regular users see only their directly assigned institutions
                    $query->whereIn('id', $userInstitutionIds);
                }
            })
            ->whereHas('tenant', function ($query) {
                $query->where('type', '!=', 'pkp');
            })
            ->with([
                'tenant:id,shortname',
                'meetings:id,title,start_time',
                'meetings.agendaItems:id,meeting_id,title,student_vote,decision,student_benefit',
                'duties.current_users:id,name',
                'duties.users:id,name,profile_photo_path',
                'duties.types:id,title,slug',
                'checkIns'
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query) {
                    $query->where('start_time', '>', now());
                }
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

}
