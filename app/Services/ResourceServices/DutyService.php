<?php

namespace App\Services\ResourceServices;

use App\Models\Institution;
use App\Models\Meeting;
use App\Services\ModelAuthorizer;
use App\Settings\AtstovavimasSettings;
use App\Settings\MeetingSettings;
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
                'meetings:id,title,start_time,type',
                'meetings.agendaItems:id,meeting_id,title,type,brought_by_students',
                'meetings.agendaItems.votes:id,agenda_item_id,title,decision,student_vote,student_benefit,is_main',
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
     * Get representative activity statistics for specific tenant IDs.
     *
     * Returns user login activity stats and categorized user lists for
     * the representative activity dashboard cards.
     *
     * @param  Collection|array  $tenantIds  The tenant IDs to get activity for
     * @return array{stats: array, users: array}
     */
    public static function getRepresentativeActivityForTenants($tenantIds): array
    {
        $tenantIds = collect($tenantIds)->filter();

        if ($tenantIds->isEmpty()) {
            return [
                'stats' => [
                    'total' => 0,
                    'activeToday' => 0,
                    'activeLast7Days' => 0,
                    'activeLast30Days' => 0,
                    'neverLoggedIn' => 0,
                ],
                'users' => [],
            ];
        }

        $user = request()->user();
        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $visibleTenantIds = $atstovavimasSettings->getVisibleTenantIds($user);

        // Filter to only accessible tenants
        $accessibleTenantIds = $tenantIds->intersect($visibleTenantIds);

        if ($accessibleTenantIds->isEmpty()) {
            return [
                'stats' => [
                    'total' => 0,
                    'activeToday' => 0,
                    'activeLast7Days' => 0,
                    'activeLast30Days' => 0,
                    'neverLoggedIn' => 0,
                ],
                'users' => [],
            ];
        }

        // Get users with active duties in the specified tenants
        $meetingSettings = app(MeetingSettings::class);
        $excludedTypeIds = $meetingSettings->getExcludedInstitutionTypeIds();

        $users = \App\Models\User::query()
            ->select('id', 'name', 'email', 'profile_photo_path', 'last_action')
            ->whereHas('current_duties', function ($query) use ($accessibleTenantIds, $excludedTypeIds) {
                $query->whereHas('institution', function ($q) use ($accessibleTenantIds, $excludedTypeIds) {
                    $q->whereIn('tenant_id', $accessibleTenantIds);

                    // Exclude institutions of excluded types (e.g., padalinys, pkp)
                    if ($excludedTypeIds->isNotEmpty()) {
                        $q->whereDoesntHave('types', function ($typeQuery) use ($excludedTypeIds) {
                            $typeQuery->whereIn('types.id', $excludedTypeIds);
                        });
                    }
                });
            })
            ->with(['current_duties' => function ($query) use ($accessibleTenantIds, $excludedTypeIds) {
                $query->select('duties.id', 'duties.name', 'duties.institution_id')
                    ->whereHas('institution', function ($q) use ($accessibleTenantIds, $excludedTypeIds) {
                        $q->whereIn('tenant_id', $accessibleTenantIds);

                        if ($excludedTypeIds->isNotEmpty()) {
                            $q->whereDoesntHave('types', function ($typeQuery) use ($excludedTypeIds) {
                                $typeQuery->whereIn('types.id', $excludedTypeIds);
                            });
                        }
                    })
                    ->with('institution:id,name,tenant_id');
            }])
            ->get()
            ->makeVisible(['last_action']);

        $now = now();
        $today = $now->copy()->startOfDay();
        $sevenDaysAgo = $now->copy()->subDays(7);
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // Calculate stats
        $stats = [
            'total' => $users->count(),
            'activeToday' => $users->filter(fn ($u) => $u->last_action && $u->last_action >= $today)->count(),
            'activeLast7Days' => $users->filter(fn ($u) => $u->last_action && $u->last_action >= $sevenDaysAgo)->count(),
            'activeLast30Days' => $users->filter(fn ($u) => $u->last_action && $u->last_action >= $thirtyDaysAgo)->count(),
            'neverLoggedIn' => $users->filter(fn ($u) => $u->last_action === null)->count(),
        ];

        // Categorize users for frontend display
        $categorizedUsers = $users->map(function ($user) use ($today, $sevenDaysAgo, $thirtyDaysAgo) {
            $lastAction = $user->last_action;

            // Determine activity category
            $category = 'never';
            if ($lastAction !== null) {
                if ($lastAction >= $today) {
                    $category = 'today';
                } elseif ($lastAction >= $sevenDaysAgo) {
                    $category = 'week';
                } elseif ($lastAction >= $thirtyDaysAgo) {
                    $category = 'month';
                } else {
                    $category = 'stale';
                }
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'last_action' => $lastAction?->toISOString(),
                'category' => $category,
                'duties' => $user->current_duties->map(fn ($duty) => [
                    'id' => $duty->id,
                    'name' => $duty->name,
                    'institution_name' => $duty->institution?->name,
                ])->values(),
            ];
        })
            ->sortBy(function ($user) {
                // Sort: never first, then by staleness (oldest last_action first)
                if ($user['category'] === 'never') {
                    return '0_';
                }

                return '1_'.($user['last_action'] ?? '');
            })
            ->values();

        return [
            'stats' => $stats,
            'users' => $categorizedUsers,
        ];
    }

    /**
     * Build the base query for dashboard institutions with all needed eager loading.
     */
    private static function buildInstitutionQuery()
    {
        return Institution::select('id', 'name', 'alias', 'tenant_id', 'meeting_periodicity_days')
            ->whereHas('tenant', function ($query) {
                $query->where('type', '!=', 'pkp');
            })
            ->with([
                'tenant:id,shortname,type', // type is needed for cross-tenant scope matching in RelationshipService
                'types', // explicit since not auto-loaded
                'meetings:id,title,start_time,type',
                'meetings.agendaItems:id,meeting_id,title,type,brought_by_students',
                'meetings.agendaItems.votes:id,agenda_item_id,title,decision,student_vote,student_benefit,is_main',
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
