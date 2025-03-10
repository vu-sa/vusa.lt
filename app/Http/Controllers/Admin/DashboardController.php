<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

// HACK: there's so much hacking here...
// TODO: 1. Refactor, so the tenant selection and authorization is done in a middleware
// TODO: 2. Non-existing tenants should 404

class DashboardController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    public function atstovavimas()
    {
        $selectedTenant = request()->input('tenant_id');

        $user = User::query()->where('id', Auth::id())->with('current_duties.institution.meetings.institutions:id,name')->first();

        // Leave only tenants that are not 'pkp'
        $tenants = collect(GetTenantsForUpserts::execute('institutions.update.padalinys', $this->authorizer))->filter(function ($tenant) {
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
        } elseif ($this->authorizer->isAllScope && request()->input('tenant_id') === '0') {
            $providedTenant = Tenant::query()->with('institutions:id,name,tenant_id', 'institutions.meetings:id,title,start_time', 'institutions.duties.current_users:id,name', 'institutions.duties.types:id,title,slug')->get();

            $providedTenant = [
                'id' => 0,
                'name' => 'Visi padaliniai',
                'institutions' => $providedTenant->map(function ($tenant) {
                    return $tenant->institutions;
                })->flatten(1),
            ];

        } else {
            $providedTenant = Tenant::query()->where('id', $selectedTenant['id'])->with('institutions:id,name,tenant_id', 'institutions.meetings:id,title,start_time', 'institutions.duties.current_users:id,name', 'institutions.duties.types:id,title,slug')->first();
        }

        return Inertia::render('Admin/Dashboard/ShowAtstovavimas', [
            'user' => [...$user->toArray(),
                'current_duties' => $user->current_duties->map(function ($duty) {
                    return [
                        ...$duty->toArray(),
                        'institution' => $duty?->institution?->append('relatedInstitutions'),
                    ];
                })],
            'tenants' => $tenants->when($this->authorizer->isAllScope, function ($tenants) {
                return $tenants->prepend(['id' => 0, 'shortname' => 'Visi padaliniai']);
            }),
            'providedTenant' => $providedTenant,
        ]);
    }

    public function svetaine()
    {
        $this->authorize('viewAny', Page::class);

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

        return Inertia::render('Admin/Dashboard/ShowSvetaine', [
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
        } else {
            $providedTenant = Tenant::query()->where('id', $selectedTenant['id'])->with('reservations.resources', 'resources')->first();

            $tenantResourceReservations = $providedTenant->resources->load('reservations.users')->pluck('reservations')->flatten()->unique('id')->values();

            $tenantResourceReservations = new Collection($tenantResourceReservations);
        }

        return Inertia::render('Admin/Dashboard/ShowReservations', [
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

        return Inertia::render('Admin/ShowUserSettings', [
            'user' => $user->toFullArray(),
        ]);
    }

    public function updateUserSettings(Request $request)
    {
        $user = User::find(Auth::id());

        if ($user->name !== $request->input('name') && ! $user->nameWasChanged) {
            $user->name_was_changed = true;
            $user->update($request->all());
        } else {
            $user->update($request->except('name'));
        }

        return redirect()->back()->with('success', 'Nustatymai išsaugoti.');
    }

    public function userTasks()
    {
        $user = User::find(auth()->user()->id);

        $tasks = $user->tasks->load('taskable', 'users');

        return Inertia::render('Admin/ShowTasks', [
            'tasks' => $tasks,
            'taskableInstitutions' => Inertia::lazy(fn () => Institution::select('id', 'name')->withWhereHas('users:users.id,users.name,profile_photo_path,phone')->get()),
        ]);
    }

    public function institutionGraph()
    {
        // return institutions with user count
        $institutions = Institution::withCount('users')->get();

        return Inertia::render('Admin/ShowInstitutionGraph', [
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
        Mail::to('it@vusa.lt')->queue(new \App\Mail\FeedbackMail($request->input('feedback'), $request->input('anonymous') ? null : auth()->user()));

        return redirect()->back()->with('success', 'Ačiū už atsiliepimą!');
    }

    public function atstovavimasSummary(Request $request, $date = null)
    {
        $selectedTenant = request()->input('tenant_id');

        $date = $date ? $date : now()->toDateString();

        // Leave only tenants that are not 'pkp'
        $tenants = collect(GetTenantsForUpserts::execute('institutions.update.padalinys', $this->authorizer))->filter(function ($tenant) {
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
            $meetings = null;
            // All tenants and all activities
        } elseif ($this->authorizer->isAllScope && request()->input('tenant_id') === '0') {
            $meetingsWithActivities = Meeting::query()
                // NOTE: some dark magic doesn't allow to filter activities in this way. In certain cases,
                // where there are no activity log that day for a meeting, it will exceed compute time of 30s.
                // ->withWhereHas('activities', function ($query) use ($date) {
                // $query->where('created_at', '>=', $date)->where('created_at', '<=', $date . ' 23:59:59');
                // })
                ->with(['institutions', 'activities.causer'])->get();

            $agendaItemsWithActivities = AgendaItem::query()->withWhereHas('activities', function ($query) use ($date) {
                $query->with('causer')->where('created_at', '>=', $date)->where('created_at', '<=', $date.' 23:59:59');
            })->with('meeting.institutions')->get();

            // Organize loaded activities by meeting
            $meetings = new Collection;

            $meetings = $meetings->merge($meetingsWithActivities);

            $agendaItemsWithActivities->each(function ($agendaItem) use (&$meetings) {
                if ($meetings->contains($agendaItem->meeting)) {
                    if (! $meetings->firstWhere('id', $agendaItem->meeting->id)->changedAgendaItems) {
                        $meetings->firstWhere('id', $agendaItem->meeting->id)->changedAgendaItems = collect();
                    }
                    $meetings->firstWhere('id', $agendaItem->meeting->id)->changedAgendaItems->push($agendaItem);
                } else {
                    $agendaItem->meeting->changedAgendaItems = collect([$agendaItem]);
                    $meetings->push($agendaItem->meeting);
                }
            });

            $providedTenant = Tenant::query()->get();

            $providedTenant = [
                'id' => 0,
                'shortname' => 'Visi padaliniai',
                'institutions' => $providedTenant->map(function ($tenant) {
                    return $tenant->institutions;
                })->flatten(1),
            ];

            // Only one tenant meeting and agenda item activities
        } else {
            $meetingsWithActivities = Meeting::query()
                ->withWhereHas('institutions', function ($query) use ($selectedTenant) {
                    $query->where('tenant_id', $selectedTenant['id']);
                })
                // NOTE: some dark magic doesn't allow to filter activities in this way. In certain cases,
                // where there are no activity log that day for a meeting, it will exceed compute time of 30s.
                // ->withWhereHas('activities', function ($query) use ($date) {
                // $query->where('created_at', '>=', $date)->where('created_at', '<=', $date . ' 23:59:59');
                // })
                ->with('activities.causer')
                ->get();

            $agendaItemsWithActivities = AgendaItem::query()->withWhereHas('meeting.institutions', function ($query) use ($selectedTenant) {
                $query->where('tenant_id', $selectedTenant['id']);
            })->withWhereHas('activities', function ($query) use ($date) {
                $query->with('causer')->where('created_at', '>=', $date)->where('created_at', '<=', $date.' 23:59:59');
            })->get();

            // Organize loaded activities by meeting
            $meetings = new Collection;

            $meetings = $meetings->merge($meetingsWithActivities);

            $agendaItemsWithActivities->each(function ($agendaItem) use ($meetings) {
                if ($meetings->contains($agendaItem->meeting)) {
                    if (! $meetings->firstWhere('id', $agendaItem->meeting->id)->changedAgendaItems) {
                        $meetings->firstWhere('id', $agendaItem->meeting->id)->changedAgendaItems = collect();
                    }
                    $meetings->firstWhere('id', $agendaItem->meeting->id)->changedAgendaItems->push($agendaItem);
                } else {
                    $agendaItem->meeting->changedAgendaItems = collect([$agendaItem]);
                    $meetings->push($agendaItem->meeting);
                }
            });

            $providedTenant = Tenant::query()->where('id', $selectedTenant['id'])->first();
        }

        // $user = User::query()->where('id', Auth::id())->with('current_duties.institution.meetings.institutions:id,name')->first();

        return Inertia::render('Admin/Dashboard/ShowAtstovavimasActivity', [
            'meetings' => $meetings->toArray(),
            'date' => $date,
            'tenants' => $tenants->when($this->authorizer->isAllScope, function ($tenants) {
                return $tenants->prepend(['id' => 0, 'shortname' => 'Visi padaliniai']);
            }),
            'providedTenant' => $providedTenant,
        ]);
    }
}
