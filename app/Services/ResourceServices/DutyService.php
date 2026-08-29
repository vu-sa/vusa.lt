<?php

namespace App\Services\ResourceServices;

use App\Enums\TenantType;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\User;
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
            ->when(! $hasGlobalAccess, function ($query) use ($tenantIds): void {
                // Only show institutions from tenants where user has permission
                $query->whereIn('tenant_id', $tenantIds);
            })
            ->whereHas('tenant', function ($query): void {
                $query->whereIn('type', TenantType::representationalValues());
            })
            ->with('tenant:id,shortname')
            ->get();
    }

    /**
     * Duties selectable as ex-officio targets for a given duty (or, when creating
     * a new duty, scoped to the tenants the user may create duties in).
     *
     * Ex-officio targets are restricted to the **same tenant** as the source duty —
     * otherwise an admin of one tenant could funnel users into (and inherit the
     * permissions of) a duty in another tenant. The only exception: admins with
     * the global duties scope (`duties.update.*` when editing / `duties.create.*`
     * when creating) may pick any duty (rare cross-tenant ex-officio cases, e.g.
     * another tenant's chairman holding an ex-officio seat).
     */
    public static function getAssignableExOfficioDuties(ModelAuthorizer $authorizer, ?Duty $duty = null): Collection
    {
        $user = request()->user();
        $authorizer = $authorizer->forUser($user);

        $query = Duty::select('id', 'name', 'institution_id')
            ->with(['institution:id,name,short_name,tenant_id', 'institution.tenant:id,shortname'])
            ->orderBy('name');

        if ($duty) {
            $query->where('id', '!=', $duty->id);

            if (! $authorizer->check('duties.update.*')) {
                $tenantId = $duty->institution?->tenant_id;
                $query->whereHas('institution', fn ($q) => $q->where('tenant_id', $tenantId));
            }
        } elseif (! $authorizer->check('duties.create.*')) {
            $tenantIds = $authorizer->getTenants('duties.create.padalinys')->pluck('id');
            $query->whereHas('institution', fn ($q) => $q->whereIn('tenant_id', $tenantIds));
        }

        return $query->get();
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
            ->when($visibleTenantIds->isNotEmpty(), function ($query) use ($visibleTenantIds, $userInstitutionIds): void {
                $query->where(function ($q) use ($visibleTenantIds, $userInstitutionIds): void {
                    $q->whereIn('tenant_id', $visibleTenantIds);

                    if ($userInstitutionIds->isNotEmpty()) {
                        $q->orWhereIn('id', $userInstitutionIds);
                    }
                });
            }, function ($query) use ($userInstitutionIds): void {
                $query->whereIn('id', $userInstitutionIds);
            })
            ->whereHas('tenant', function ($query): void {
                $query->whereIn('type', TenantType::representationalValues());
            })
            ->with([
                'tenant:id,shortname',
                'types', // explicit since not auto-loaded
                'meetings:id,title,start_time,type',
                'meetings.agendaItems:id,meeting_id,title,type,brought_by_students',
                'meetings.agendaItems.votes:id,agenda_item_id,title,decision,student_vote,student_benefit,is_main',
                'meetings.calendarEvent:id,meeting_id,is_draft',
                // Historical assignments are required for Gantt coverage periods.
                'duties.users:id,name,profile_photo_path,last_action',
                'duties.types:id,title,slug',
                'checkIns',
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query): void {
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
     * Get institutions for specific tenant IDs, with a light eager-load set for the
     * ViSAK tenant timeline API.
     *
     * Unlike {@see getInstitutionsForTenants()}, this skips agenda items, votes and
     * file metadata: the timeline payload contains no meetings (those are loaded in
     * windows via the meetings endpoint), and meetings are only needed for the
     * server-side activity-status resolution.
     *
     * @param  Collection|array  $tenantIds  The tenant IDs to load institutions for
     * @param  ModelAuthorizer  $authorizer  The authorizer to check access permissions
     */
    public static function getTimelineInstitutionsForTenants($tenantIds, ModelAuthorizer $authorizer)
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

        return Institution::select('id', 'name', 'alias', 'tenant_id', 'meeting_periodicity_days')
            ->whereIn('tenant_id', $accessibleTenantIds)
            ->whereHas('tenant', function ($query): void {
                $query->whereIn('type', TenantType::representationalValues());
            })
            ->with([
                'tenant:id,shortname,type',
                'types', // needed for has_public_meetings and meeting_periodicity_days
                // Meetings only carry start_time: enough for activity-status resolution.
                'meetings:id,start_time',
                // Historical assignments are required for Gantt coverage periods.
                'duties.users:id,name,profile_photo_path,last_action',
                'checkIns',
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query): void {
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
     * Build the base query for dashboard institutions with all needed eager loading.
     */
    private static function buildInstitutionQuery()
    {
        return Institution::select('id', 'name', 'alias', 'tenant_id', 'meeting_periodicity_days')
            ->whereHas('tenant', function ($query): void {
                $query->whereIn('type', TenantType::representationalValues());
            })
            ->with([
                'tenant:id,shortname,type', // type is needed for cross-tenant scope matching in RelationshipService
                'types', // explicit since not auto-loaded
                'meetings:id,title,start_time,type',
                'meetings.agendaItems:id,meeting_id,title,type,brought_by_students',
                'meetings.agendaItems.votes:id,agenda_item_id,title,decision,student_vote,student_benefit,is_main',
                // Load fileableFiles for has_report and has_protocol accessors (prevents N+1)
                'meetings.fileableFiles:id,fileable_id,fileable_type,file_type,deleted_externally_at',
                // Same reason for has_calendar_event, which the Gantt tooltip reads.
                'meetings.calendarEvent:id,meeting_id,is_draft',
                // Historical assignments are required for Gantt coverage periods.
                'duties.users:id,name,profile_photo_path,last_action',
                'duties.types:id,title,slug',
                'checkIns',
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query): void {
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
