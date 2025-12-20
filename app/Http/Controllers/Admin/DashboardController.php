<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Services\ResourceServices\DutyService;
use App\Settings\AtstovavimasSettings;
use App\Settings\MeetingSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class DashboardController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    public function index(Request $request)
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);
        $userTenantId = $user->current_duties->first()->institution->tenant_id ?? null;

        // TODO: for some reasoning, the chaining doesn't work
        $this->authorizer = $this->authorizer->forUser($user);

        // Get notification count
        $unreadNotificationsCount = $user->unreadNotifications()->count();

        // Get task statistics for the dashboard
        $taskStats = [
            'total' => $user->tasks()->whereNull('completed_at')->count(),
            'overdue' => $user->tasks()->whereNull('completed_at')->where('due_date', '<', now())->count(),
            'dueSoon' => $user->tasks()->whereNull('completed_at')
                ->where('due_date', '>=', now())
                ->where('due_date', '<=', now()->addDays(7))
                ->count(),
        ];

        // Get upcoming tasks (due within 14 days or overdue)
        $upcomingTasks = $user->tasks()
            ->whereNull('completed_at')
            ->where(function ($query) {
                $query->where('due_date', '<=', now()->addDays(14))
                    ->orWhere('due_date', '<', now());
            })
            ->orderByRaw('CASE WHEN due_date < ? THEN 0 ELSE 1 END', [now()]) // Overdue first
            ->orderBy('due_date')
            ->with('taskable')
            ->take(10)
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'name' => $task->name,
                'due_date' => $task->due_date?->toISOString(),
                'is_overdue' => $task->due_date && $task->due_date < now(),
                'taskable_type' => class_basename($task->taskable_type ?? ''),
                'taskable_id' => $task->taskable_id,
            ]);

        // Get user's institutions and upcoming meetings
        $userInstitutionIds = $user->current_duties->pluck('institution_id')->filter()->unique();

        $upcomingMeetings = Meeting::query()
            ->whereHas('institutions', fn ($q) => $q->whereIn('institutions.id', $userInstitutionIds))
            ->where('start_time', '>', now())
            ->where('start_time', '<', now()->addMonths(2))
            ->orderBy('start_time')
            ->with(['institutions:id,name'])
            ->take(3)
            ->get()
            ->map(fn ($meeting) => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'start_time' => $meeting->start_time?->toISOString(),
                'institution_name' => $meeting->institutions->first()?->name,
            ]);

        // Get institutions needing attention (overdue meetings based on periodicity)
        $meetingSettings = app(MeetingSettings::class);
        $excludedTypeIds = $meetingSettings->getExcludedInstitutionTypeIds();

        $userInstitutions = Institution::query()
            ->whereIn('id', $userInstitutionIds)
            ->with(['meetings' => fn ($q) => $q->orderByDesc('start_time')->take(1), 'types'])
            ->get()
            ->filter(function ($institution) use ($excludedTypeIds) {
                // Exclude institutions with excluded types
                if ($excludedTypeIds->isNotEmpty()) {
                    return $institution->types->pluck('id')->intersect($excludedTypeIds)->isEmpty();
                }

                return true;
            });

        $institutionsNeedingAttention = $userInstitutions
            ->map(function ($institution) {
                $lastMeeting = $institution->meetings->first();
                $periodicity = $institution->meeting_periodicity_days ?? 30;

                if (! $lastMeeting) {
                    return [
                        'id' => $institution->id,
                        'name' => $institution->name,
                        'days_since_last_meeting' => null,
                        'periodicity' => $periodicity,
                        'status' => 'no_meetings',
                    ];
                }

                $daysSinceLastMeeting = (int) now()->diffInDays($lastMeeting->start_time);
                $isOverdue = $daysSinceLastMeeting > $periodicity;
                $isApproaching = ! $isOverdue && $daysSinceLastMeeting >= ($periodicity * 0.8);

                if (! $isOverdue && ! $isApproaching) {
                    return null;
                }

                return [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    'days_since_last_meeting' => $daysSinceLastMeeting,
                    'periodicity' => $periodicity,
                    'status' => $isOverdue ? 'overdue' : 'approaching',
                    'last_meeting_date' => $lastMeeting->start_time->toISOString(),
                ];
            })
            ->filter()
            ->sortByDesc(fn ($i) => ($i['days_since_last_meeting'] ?? 999) - $i['periodicity'])
            ->take(3)
            ->values();

        // Get upcoming calendar events (non-draft, future) - return full models for EventCard component
        $upcomingCalendarEvents = Calendar::query()
            ->where('is_draft', false)
            ->where('date', '>=', now())
            ->with(['tenant:id,shortname', 'category:id,name'])
            ->orderBy('date')
            ->take(3)
            ->get();

        // Get latest published news - return full models for NewsCard component
        $locale = app()->getLocale();
        $latestNews = News::query()
            ->where('draft', false)
            ->whereNotNull('publish_time')
            ->where('publish_time', '<=', now())
            ->where('lang', $locale)
            ->with(['tenant:id,shortname'])
            ->orderByDesc('publish_time')
            ->take(3)
            ->get();

        return $this->inertiaResponse('Admin/ShowAdminHome', [
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'hasNotifications' => $unreadNotificationsCount > 0,
            'taskStats' => $taskStats,
            'upcomingTasks' => $upcomingTasks,
            'upcomingMeetings' => $upcomingMeetings,
            'institutionsNeedingAttention' => $institutionsNeedingAttention,
            'upcomingCalendarEvents' => $upcomingCalendarEvents,
            'latestNews' => $latestNews,
        ]);
    }

    public function atstovavimas()
    {
        // Get basic user info with duty institution IDs only
        $user = User::query()->where('id', Auth::id())
            ->with(['current_duties:id,name,institution_id'])
            ->first();

        // Get only user's directly assigned institutions (lightweight, always loaded)
        $userInstitutions = DutyService::getUserInstitutionsForDashboard();

        // Filter out institutions with excluded types (e.g., padalinys, pkp - institutions that don't have formal meetings)
        $excludedTypeIds = app(\App\Settings\MeetingSettings::class)->getExcludedInstitutionTypeIds();
        if ($excludedTypeIds->isNotEmpty()) {
            $userInstitutions = $userInstitutions->filter(function ($institution) use ($excludedTypeIds) {
                // Exclude institution if any of its types are in the excluded list
                return $institution->types->pluck('id')->intersect($excludedTypeIds)->isEmpty();
            })->values();
        }

        // Helper function to append computed attributes to institutions
        $appendInstitutionAttributes = function ($institutions) {
            $institutions->each(function ($institution) {
                $institution->meetings?->each->append('completion_status');
                // Add active_check_in from already-loaded checkIns
                $institution->active_check_in = $institution->checkIns
                    ?->where('end_date', '>=', now())
                    ->where('start_date', '<=', now())
                    ->first() ?? null;
                // Append has_public_meetings for UI indicators (uses already-loaded types relation)
                $institution->append('has_public_meetings');
                // Append meeting_periodicity_days for overdue warnings (inherits from types, defaults to 30)
                $institution->append('meeting_periodicity_days');
            });

            return $institutions;
        };

        // Append computed attributes to user institutions
        $appendInstitutionAttributes($userInstitutions);

        // Get available tenants for filtering - only for coordinators and admins
        // Regular users should not see the tenant tab (they only see their assigned institutions)
        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $hasGlobalAccess = $this->authorizer->forUser($user)->checkAllRoleables('duties.create.all');
        $coordinatorTenantIds = $atstovavimasSettings->getCoordinatorTenantIds($user);

        if ($hasGlobalAccess) {
            // Super admins see all tenants
            $availableTenants = Tenant::query()
                ->where('type', '!=', 'pkp')
                ->orderBy('shortname_vu')
                ->get(['id', 'shortname', 'type'])
                ->map(fn ($tenant) => [
                    'id' => $tenant->id,
                    'shortname' => __($tenant->shortname),
                    'type' => $tenant->type,
                ]);
        } elseif ($coordinatorTenantIds->isNotEmpty()) {
            // Coordinators see only tenants where they have coordinator access
            $availableTenants = Tenant::query()
                ->whereIn('id', $coordinatorTenantIds)
                ->where('type', '!=', 'pkp')
                ->orderBy('shortname_vu')
                ->get(['id', 'shortname', 'type'])
                ->map(fn ($tenant) => [
                    'id' => $tenant->id,
                    'shortname' => __($tenant->shortname),
                    'type' => $tenant->type,
                ]);
        } else {
            // Regular users don't see the tenant tab
            $availableTenants = collect();
        }

        // Derive recent meetings from user's institutions only (lightweight)
        $sixMonthsAgo = now()->subMonths(6);
        $recentMeetings = $userInstitutions
            ->flatMap(function ($institution) use ($sixMonthsAgo) {
                return $institution->meetings
                    ?->filter(fn ($meeting) => $meeting->start_time >= $sixMonthsAgo)
                    ->map(fn ($meeting) => [
                        'id' => (string) $meeting->id,
                        'title' => $meeting->title,
                        'start_time' => $meeting->start_time?->toISOString(),
                        'institution_name' => $institution->name ?? 'Unknown',
                        'agenda_items' => $meeting->agendaItems->map(fn ($item) => ['title' => $item->title])->toArray(),
                    ]) ?? collect();
            })
            ->sortByDesc('start_time')
            ->unique('id')
            ->take(10)
            ->values();

        // Quick check if user might have related institutions (without loading them)
        // This enables the filter UI even when relatedInstitutions is lazy-loaded
        $mayHaveRelatedInstitutions = $userInstitutions->isNotEmpty();

        return $this->inertiaResponse('Admin/Dashboard/ShowAtstovavimas', [
            'user' => [
                ...$user->toArray(),
                'current_duties' => $user->current_duties->map(function ($duty) use ($userInstitutions) {
                    $institution = $userInstitutions->firstWhere('id', $duty->institution_id);

                    return [
                        ...$duty->toArray(),
                        'institution' => $institution,
                    ];
                }),
            ],
            // User's own institutions - always loaded (lightweight)
            'userInstitutions' => $userInstitutions->values(),
            // Quick flag to show/hide related institutions filter (lazy data may not be loaded yet)
            'mayHaveRelatedInstitutions' => $mayHaveRelatedInstitutions,
            // Lazy load relatedInstitutions - only fetched when explicitly requested via Inertia reload
            'relatedInstitutions' => Inertia::lazy(function () use ($userInstitutions) {
                $relatedInstitutions = RelationshipService::getRelatedInstitutionsForMultiple(
                    new Collection($userInstitutions->values()->all())
                );

                // Append completion_status to related institution meetings and other computed attributes
                $relatedInstitutions->each(function ($institution) {
                    $institution->meetings?->each->append('completion_status');
                    $institution->append('has_public_meetings');
                    $institution->append('meeting_periodicity_days');
                });

                return $relatedInstitutions->values();
            }),
            // Lazy load tenant institutions - only fetched when tenant tab is opened
            // Expects 'tenantIds' parameter in the reload request
            'tenantInstitutions' => Inertia::lazy(function () use ($excludedTypeIds, $appendInstitutionAttributes) {
                $tenantIds = request()->input('tenantIds', []);
                $institutions = DutyService::getInstitutionsForTenants($tenantIds, $this->authorizer);

                // Apply same filtering as user institutions
                if ($excludedTypeIds->isNotEmpty()) {
                    $institutions = $institutions->filter(function ($institution) use ($excludedTypeIds) {
                        return $institution->types->pluck('id')->intersect($excludedTypeIds)->isEmpty();
                    })->values();
                }

                // Append computed attributes
                $appendInstitutionAttributes($institutions);

                return $institutions->values();
            }),
            'availableTenants' => $availableTenants,
            'recentMeetings' => $recentMeetings,
        ]);
    }

    public function svetaine()
    {
        $this->handleAuthorization('viewAny', Page::class);

        $selectedTenant = request()->input('tenant_id');

        // Leave only tenants that are not 'pkp'
        $tenants = collect(GetTenantsForUpserts::execute('pages.update.padalinys', $this->authorizer))->filter(function ($tenant) {
            return $tenant['type'] !== 'pkp';
        })->values();

        // Check if selected tenant is in the list of tenants
        if ($selectedTenant) {
            $selectedTenant = $tenants->firstWhere('id', $selectedTenant);
        } else {
            // Check if there's tenant with type 'pagrindinis'
            $selectedTenant = $tenants->firstWhere('type', 'pagrindinis');
        }

        // If not, select first tenant
        if (! $selectedTenant) {
            $selectedTenant = $tenants->first();
        }

        if (! $selectedTenant) {
            $providedTenant = null;
        } else {
            $providedTenant = Tenant::query()->where('id', $selectedTenant['id'])->with('pages', 'news', 'quickLinks')->with(['calendar' => function ($query) {
                // get only future and pasts event 12 months ago
                $query->where('date', '>=', now()->subMonths(12))->orderBy('date', 'asc');
            }])->first();
        }

        return $this->inertiaResponse('Admin/Dashboard/ShowSvetaine', [
            'tenants' => $tenants,
            'providedTenant' => $providedTenant,
        ]);
    }

    public function reservations()
    {
        $selectedTenant = request()->input('tenant_id');

        $user = User::find(Auth::id());

        $reservations = $user->reservations->load('resources')->append('isCompleted');

        // Leave only tenants that are not 'pkp'
        $tenants = collect(GetTenantsForUpserts::execute('reservations.update.padalinys', $this->authorizer));

        // Check if selected tenant is in the list of tenants
        if ($selectedTenant) {
            $selectedTenant = $tenants->firstWhere('id', $selectedTenant);
        } else {
            // Check if there's tenant with type 'pagrindinis'
            $selectedTenant = $tenants->firstWhere('type', 'pagrindinis');
        }

        // If not, select first tenant
        if (! $selectedTenant) {
            $selectedTenant = $tenants->first();
        }

        if (! $selectedTenant) {
            $providedTenant = null;
            $tenantResourceReservations = new Collection;
        } else {
            $providedTenant = Tenant::query()->where('id', $selectedTenant['id'])->with('reservations.resources', 'resources')->first();

            $tenantResourceReservations = $providedTenant->resources->load('reservations.users:id,name,email,profile_photo_path')->pluck('reservations')->flatten()->unique('id')->values();

            $tenantResourceReservations = new Collection($tenantResourceReservations);
        }

        return $this->inertiaResponse('Admin/Dashboard/ShowReservations', [
            'reservations' => $reservations,
            'resources' => [
                'active' => Resource::where('is_reservable', true)->count(),
                'sumOfCapacity' => Resource::where('is_reservable', true)->sum('capacity'),
            ],
            'tenants' => $tenants,
            'providedTenant' => $providedTenant ? [
                ...$providedTenant->toArray(),
                'reservations' => $providedTenant->reservations->load('resources.tenant', 'users:id,name,email,profile_photo_path')->append('isCompleted')->unique('id')->values(),
                'activeReservations' => $tenantResourceReservations->append('isCompleted'),
            ] : null,
        ]);
    }

    public function userSettings()
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);

        $user->load('roles:id,name',
            'current_duties:id,name,institution_id',
            'current_duties.roles:id,name', 'current_duties.roles.permissions:id,name',
            'current_duties.institution:id,tenant_id',
            'current_duties.institution.tenant:id,shortname')->makeVisible(['name_was_changed', 'show_pronouns']);

        return $this->inertiaResponse('Admin/ShowUserSettings', [
            'user' => $user->append('has_password')->toFullArray(),
        ]);
    }

    public function updateUserSettings(Request $request)
    {
        $user = User::find(Auth::id());

        if ($user->name !== $request->input('name') && ! $user->name_was_changed) {
            $user->name_was_changed = true;
            $user->update($request->all());
        } else {
            $user->update($request->except('name'));
        }

        return $this->redirectBackWithSuccess('Nustatymai išsaugoti.');
    }

    public function updatePassword(\App\Http\Requests\UpdatePasswordRequest $request)
    {
        $user = User::find(Auth::id());

        $user->password = bcrypt($request->password);
        $user->save();

        return redirect()->back()->with('success', 'Slaptažodis sėkmingai pakeistas.');
    }

    public function userTasks()
    {
        $user = User::find(Auth::id());

        $tasks = $user->tasks->load('taskable', 'users:id,name,email,profile_photo_path');

        return $this->inertiaResponse('Admin/ShowTasks', [
            'tasks' => $tasks,
            'taskableInstitutions' => Inertia::lazy(fn () => Institution::select('id', 'name')->withWhereHas('users:users.id,users.name,users.email,users.profile_photo_path')->get()),
        ]);
    }

    public function institutionGraph()
    {
        // return institutions with user count
        $institutions = Institution::withCount('users')->get();

        return $this->inertiaResponse('Admin/ShowInstitutionGraph', [
            'institutions' => $institutions,
            'institutionRelationships' => RelationshipService::getAllRelatedInstitutions(),
        ]);
    }

    public function sendFeedback(Request $request)
    {
        $request->validate([
            'feedback' => 'required|string',
            'anonymous' => 'boolean',
        ]);

        // just send simple email to it@vusa.lt with feedback, conditional user name and with in a queue
        Mail::to('it@vusa.lt')->queue(new \App\Mail\FeedbackMail($request->input('feedback'), $request->input('anonymous') ? null : Auth::user()));

        return $this->redirectBackWithSuccess('Ačiū už atsiliepimą!');
    }

    // Removed atstovavimasSummary endpoint and related view as per simplification request.
}
