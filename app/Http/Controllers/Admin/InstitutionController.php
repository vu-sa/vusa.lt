<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetInstitutionAdministrators;
use App\Actions\GetInstitutionMembers;
use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexInstitutionRequest;
use App\Http\Requests\ReorderDutiesRequest;
use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Http\Resources\InstitutionMeetingResource;
use App\Http\Resources\TaskResource;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Type;
use App\Services\InstitutionActivityStatusService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Services\TanstackTableService;
use App\Settings\CadenceSettings;
use App\Support\MorphMap;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(
        public Authorizer $authorizer,
        private TanstackTableService $tableService,
        private readonly InstitutionActivityStatusService $activityStatusService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexInstitutionRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Institution::class);

        // Build base query with eager loading
        // Newest meetings first — the index cell shows only the first few, and
        // the recent ones are what an administrator is looking for.
        $query = Institution::query()->with(['meetings' => fn ($query) => $query->orderByDesc('start_time'), 'tenant', 'types']);

        // Define searchable columns
        $searchableColumns = ['name', 'alias', 'email', 'tenant.name'];

        // Apply Tanstack Table filters
        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'tenantRelation' => 'tenant',
                'permission' => 'institutions.read.padalinys',
                'applySortBeforePagination' => true, // Ensure sorting is applied before pagination
            ]
        );

        // Paginate results
        $deletedCount = $this->getTrashedCount($query);

        // Trash view only: lets the table say why permanent deletion is refused.
        $query = $this->withForceDeleteBlockers($query, $request, ['meetings', 'duties', 'checkIns']);

        $institutions = $query->paginate($request->getPerPage())
            ->withQueryString();

        $this->appendForceDeleteBlockedReason($institutions->getCollection(), $request);

        // Get institution types for filtering
        $types = Type::where('model_type', MorphMap::alias(Institution::class))->get();

        // Get the sorting state using the custom method to ensure consistent parsing
        $sorting = $request->getSorting();

        // Return response with all necessary data
        return $this->inertiaResponse('Admin/People/IndexInstitution', [
            'data' => $institutions->items(),
            'meta' => [
                'total' => $institutions->total(),
                'per_page' => $institutions->perPage(),
                'current_page' => $institutions->currentPage(),
                'last_page' => $institutions->lastPage(),
                'from' => $institutions->firstItem(),
                'to' => $institutions->lastItem(),
            ],
            'types' => $types,
            'filters' => $request->getFilters(),
            'sorting' => $sorting, // Pass properly parsed sorting state to frontend
            'initialSorting' => $sorting, // Add initial sorting to persist state on first load
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Institution::class);

        Inertia::share('seo.title', 'Nauja institucija');

        return $this->inertiaResponse('Admin/People/CreateInstitution', [
            'assignableTenants' => GetTenantsForUpserts::execute('institutions.create.padalinys', $this->authorizer),
            'institutionTypes' => Type::where('model_type', MorphMap::alias(Institution::class))->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInstitutionRequest $request)
    {
        $institution = new Institution;

        $institution->fill($request->safe()->except('types'))->save();

        $institution->syncAudited('types', $request->types);

        // Load relationships needed for the response
        $institution->load('tenant:id,shortname', 'types');

        // Return JSON response for AJAX requests (inline creation in wizard)
        if ($request->wantsJson() || $request->header('X-Inertia-Partial-Data')) {
            return response()->json([
                'success' => true,
                'message' => $this->entityMessage('created', 'institution'),
                'institution' => $institution,
            ]);
        }

        return back()->with('success', $this->entityMessage('created', 'institution'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Institution $institution)
    {
        $this->handleAuthorization('view', $institution);

        $institution->load('tenant:id,shortname', 'types', 'duties.current_users', 'checkIns')
            ->loadCount(['comments', 'duties', 'meetings', 'tasks', 'tasksFromMeetings']);

        $institution->append(['has_public_meetings', 'meeting_periodicity_days', 'governance_scope']);
        $activityStatus = $this->activityStatusService->resolve($institution)->toArray();
        $tasksCount = (int) $institution->getAttribute('tasks_count');
        $tasksFromMeetingsCount = (int) $institution->getAttribute('tasks_from_meetings_count');
        $recentComments = $institution->comments()
            ->roots()
            ->with('user:id,name,profile_photo_path')
            ->withCount('replies')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Comment $comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'kind' => $comment->kind->value,
                'created_at' => $comment->created_at->toISOString(),
                'replies_count' => $comment->replies_count,
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'profile_photo_path' => $comment->user->profile_photo_path,
                ] : null,
            ]);

        $overviewMeetings = $institution->meetings()
            ->withCount('agendaItems')
            ->with(['agendaItems.votes', 'fileableFiles', 'institutions.types'])
            ->orderByDesc('start_time')
            ->limit(3)
            ->get()
            ->each->append(['has_report', 'has_protocol']);

        $overviewDuties = $institution->duties
            ->sortBy('order')
            ->map(fn (Duty $duty) => [
                'id' => $duty->id,
                'name' => $duty->name,
                'order' => $duty->order,
                'places_to_occupy' => $duty->places_to_occupy,
                'current_users' => $duty->current_users,
            ])
            ->values();

        // Get subscription status for the current user
        $user = request()->user();
        $subscriptionStatus = $user ? [
            'is_followed' => $user->follows($institution),
            'is_muted' => $user->isInstitutionMuted($institution),
            'is_duty_based' => $user->hasInstitution($institution),
        ] : null;

        // Inertia::share('layout.navBackground', $institution->image_url ?? null);

        return $this->inertiaResponse('Admin/People/ShowInstitution', [
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'short_name' => $institution->short_name,
                'description' => $institution->description,
                'types' => $institution->types,
                'has_public_meetings' => $institution->has_public_meetings,
                'meeting_periodicity_days' => $institution->meeting_periodicity_days,
                'governance_scope' => $institution->governance_scope,
                'comments_count' => $institution->comments_count,
                'duties_count' => $institution->duties_count,
                'meetings_count' => $institution->meetings_count,
                'tasks_count' => $tasksCount + $tasksFromMeetingsCount,
                'related_institutions_count' => RelationshipService::getRelatedInstitutionsCached($institution)->count(),
                'managers' => $institution->managers(),
                'administrators' => InstitutionAdministratorController::usersPayload(
                    GetInstitutionAdministrators::execute($institution)
                ),
                'sharepointPath' => $institution->tenant ? $institution->sharepoint_path() : null,
            ],
            'overview' => [
                'activity_status' => $activityStatus,
                'current_users' => $institution->duties->pluck('current_users')->flatten()->unique('id')->values(),
                'duties' => $overviewDuties,
                'recentMeetings' => InstitutionMeetingResource::collection($overviewMeetings)->resolve(),
                'meetings_count' => $institution->meetings_count,
                'recentComments' => $recentComments,
            ],
            'duties' => Inertia::defer(fn () => $institution->duties()
                ->with('current_users')
                ->orderBy('order')
                ->get()
                ->toArray(), 'institutionPanels'),
            'meetings' => Inertia::defer(fn () => InstitutionMeetingResource::collection(
                $institution->meetings()
                    ->withCount('agendaItems')
                    ->with(['agendaItems.votes', 'fileableFiles', 'institutions.types'])
                    ->orderByDesc('start_time')
                    ->get()
                    ->each->append(['has_report', 'has_protocol'])
            )->resolve(), 'institutionPanels'),
            'tasks' => Inertia::defer(fn () => TaskResource::collection(
                $institution->tasks()
                    ->with('users:id,name,email,profile_photo_path', 'taskable')
                    ->get()
                    ->merge($institution->tasksFromMeetings()
                        ->with('users:id,name,email,profile_photo_path', 'taskable')
                        ->get())
                    ->sortByDesc('created_at')
                    ->values()
            )->resolve(), 'institutionPanels'),
            'relatedInstitutions' => Inertia::defer(fn () => RelationshipService::getRelatedInstitutionsCached($institution)
                ->map(fn (array $item) => [
                    'id' => $item['institution']->id,
                    'name' => $item['institution']->name,
                    'direction' => $item['direction'],
                    'type' => $item['type'],
                    'authorized' => $item['authorized'],
                ])
                ->values()
                ->all(), 'institutionPanels'),
            'subscription' => $subscriptionStatus,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Institution $institution)
    {
        $this->handleAuthorization('update', $institution);

        $institution->load('types')->load(['duties' => function ($query): void {
            $query->with([
                'current_users',
                // Load the most recent previous user for duties without current users
                'previous_users' => fn ($q) => $q->orderByPivot('end_date', 'desc')->limit(1),
            ])->orderBy('order', 'asc');
        }]);

        Inertia::share('seo.title', $institution->name);

        return $this->inertiaResponse('Admin/People/EditInstitution', [
            'institution' => [
                ...$institution->toFullArray(),
                'types' => $institution->types->pluck('id'),
            ],
            'institutionTypes' => Type::where('model_type', MorphMap::alias(Institution::class))->get(),
            'assignableTenants' => GetTenantsForUpserts::execute('institutions.update.padalinys', $this->authorizer),
            // Term boundaries are edited here rather than in settings, because they belong
            // to the body that uses them. The global ladder rides along read-only so the
            // editor can see what they would be overriding.
            'cadences' => CadenceController::payload($institution->id),
            'globalCadences' => CadenceController::payload(globalOnly: true),
            'cadenceDefaults' => [
                'default_start_month_day' => app(CadenceSettings::class)->default_start_month_day,
                'default_end_month_day' => app(CadenceSettings::class)->default_end_month_day,
            ],
            // One roster per applicable term, edited beside the terms themselves.
            'administratorRosters' => InstitutionAdministratorController::payload($institution),
            // Suggested first in the picker: the people already in the body.
            'suggestedAdministrators' => InstitutionAdministratorController::usersPayload(
                GetInstitutionMembers::execute($institution)
            ),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInstitutionRequest $request, Institution $institution)
    {
        $institution->fill($request->safe()->except('tenant_id', 'types'));

        // check if super admin, then update tenant_id
        if (auth()->user()->isSuperAdmin()) {
            $institution->fill($request->safe()->only('tenant_id'));
        }

        $institution->save();

        // get only types id
        $institution->syncAudited('types', $request->types);

        return back()->with('success', $this->entityMessage('updated', 'institution'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Institution $institution)
    {
        $this->handleAuthorization('delete', $institution);

        // check if auth user is from this institution
        if (auth()->user()->institutions->contains($institution)) {
            return back()->with('error', __('messages.institution.cannot_delete_own'));
        }

        $institution->delete();

        return redirect()->route('institutions.index')->with('info', $this->entityMessage('deleted', 'institution'));
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Institution $institution): RedirectResponse
    {
        return $this->restoreModel($institution, $this->entityMessage('restored', 'institution'));
    }

    /**
     * reorderDuties
     * Duties are ordered in the frontend array by the user. The order is saved in the database
     *
     * @return RedirectResponse
     */
    public function reorderDuties(ReorderDutiesRequest $request)
    {
        $duties = collect($request->validated('duties'));

        $dutyModels = Duty::whereIn('id', $duties->pluck('id'))->get()->keyBy('id');

        foreach ($duties as $duty) {
            $dutyModel = $dutyModels->get($duty['id']);

            if ($dutyModel) {
                $dutyModel->order = $duty['order'];
                $dutyModel->save();
            }
        }

        return back()->with('success', __('messages.duty.order_updated'));
    }

    public function forceDelete(Institution $institution): RedirectResponse
    {
        return $this->forceDeleteModel($institution);
    }
}
