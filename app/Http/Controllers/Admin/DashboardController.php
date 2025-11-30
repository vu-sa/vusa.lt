<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Page;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Services\ResourceServices\DutyService;
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

        // Get task statistics for the dashboard
        $taskStats = [
            'completed' => $user->tasks()->where('completed_at', '!=', null)->count(),
            'pending' => $user->tasks()->where('completed_at', null)->where('due_date', '>=', now())->count(),
            'overdue' => $user->tasks()->where('completed_at', null)->where('due_date', '<', now())->count(),
        ];

        // Get notification count
        $unreadNotificationsCount = $user->unreadNotifications()->count();

        return $this->inertiaResponse('Admin/ShowAdminHome', [
            'taskStats' => $taskStats,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'hasNotifications' => $unreadNotificationsCount > 0,
            'user' => $user,
        ]);
    }

    public function atstovavimas()
    {
        $user = User::query()->where('id', Auth::id())
            ->with([
                'current_duties.institution' => function ($query) {
                    $query->select('id', 'name', 'tenant_id')
                        ->with([
                            'meetings' => function ($meetingQuery) {
                                $meetingQuery->select('id', 'title', 'start_time')
                                    ->with('agendaItems:id,meeting_id,student_vote,decision,student_benefit')
                                    ->orderBy('start_time', 'desc');
                            },
                        ])
                        ->with('activeCheckIns')
                        ->with('checkIns')
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
                            'days_since_last_meeting' => DutyService::getDaysSinceLastMeetingSql(),
                        ]);
                },
            ])->first();

        // Get ALL institutions user can access with dashboard data
        $accessibleInstitutions = DutyService::getInstitutionsForDashboard($this->authorizer);

        // Append completion_status to meetings for dashboard Gantt chart
        $accessibleInstitutions->each(function ($institution) {
            $institution->meetings?->each->append('completion_status');
        });

        // Get available tenants for filtering
        $availableTenants = collect(GetTenantsForUpserts::execute('institutions.update.padalinys', $this->authorizer))
            ->filter(function ($tenant) {
                return $tenant['type'] !== 'pkp';
            })
            ->values();

        // Get recent meetings for templates
        $recentMeetings = Meeting::with(['institutions', 'agendaItems'])
            ->whereHas('institutions', function ($query) use ($accessibleInstitutions) {
                $query->whereIn('institutions.id', $accessibleInstitutions->pluck('id'));
            })
            ->where('start_time', '>=', now()->subMonths(6))
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($meeting) {
                return [
                    'id' => (string) $meeting->id,
                    'title' => $meeting->title,
                    'start_time' => $meeting->start_time?->toISOString(),
                    'institution_name' => $meeting->institutions->first()?->name ?? 'Unknown',
                    'agenda_items' => $meeting->agendaItems->map(fn ($item) => ['title' => $item->title])->toArray(),
                ];
            });

        return $this->inertiaResponse('Admin/Dashboard/ShowAtstovavimas', [
            'user' => [
                ...$user->toArray(),
                'current_duties' => $user->current_duties->map(function ($duty) {
                    $institution = $duty->institution;
                    if ($institution) {
                        // Calculate meeting frequency and health metrics
                        $recentMeetings = $institution->meetings->where('start_time', '>=', now()->subMonths(3));
                        $institution->meeting_frequency = $recentMeetings->count() > 0
                            ? round(3 * 4 / $recentMeetings->count(), 1) // weeks between meetings
                            : null;

                        // Health score based on recent activity
                        $healthScore = 100;
                        if ($institution->days_since_last_meeting > 60) {
                            $healthScore -= 40;
                        } elseif ($institution->days_since_last_meeting > 30) {
                            $healthScore -= 20;
                        }
                        if ($institution->upcoming_meetings_count === 0) {
                            $healthScore -= 30;
                        }

                        $institution->health_score = max(0, $healthScore);

                        $institution->active_check_in = $institution->activeCheckIns->first() ?? null;
                        $institution = $institution->append('relatedInstitutions');

                        // Append completion_status to each meeting for dashboard display
                        $institution->meetings->each->append('completion_status');
                    }

                    return [
                        ...$duty->toArray(),
                        'institution' => $institution,
                    ];
                }),
            ],
            'accessibleInstitutions' => $accessibleInstitutions,
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

            $tenantResourceReservations = $providedTenant->resources->load('reservations.users')->pluck('reservations')->flatten()->unique('id')->values();

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
                'reservations' => $providedTenant->reservations->load('resources.tenant', 'users')->append('isCompleted')->unique('id')->values(),
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

        $tasks = $user->tasks->load('taskable', 'users');

        return $this->inertiaResponse('Admin/ShowTasks', [
            'tasks' => $tasks,
            'taskableInstitutions' => Inertia::lazy(fn () => Institution::select('id', 'name')->withWhereHas('users:users.id,users.name,profile_photo_path,phone')->get()),
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
