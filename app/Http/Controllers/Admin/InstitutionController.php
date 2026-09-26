<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetInstitutionMembers;
use App\Actions\GetInstitutionSecretaries;
use App\Actions\GetTenantsForUpserts;
use App\Actions\GetTypeFiles;
use App\Actions\GetUserTenantShortnames;
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
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\StudyProgram;
use App\Models\Task;
use App\Models\Type;
use App\Services\InstitutionActivityStatusService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Services\ResourceServices\SharepointFileService;
use App\Settings\CadenceSettings;
use App\Settings\MeetingSettings;
use App\Support\MorphMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(
        public Authorizer $authorizer,
        private readonly InstitutionActivityStatusService $activityStatusService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexInstitutionRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Institution::class);

        // Live rows come from Typesense (the scoped key carries the authorization) and the trash
        // from api.v1.admin.trash.index, so the page itself needs no rows.
        return $this->inertiaResponse('Admin/People/IndexInstitution', [
            'deletedCount' => $this->scopedTrashedCount(Institution::query(), 'tenant', 'institutions.read.padalinys'),
            // Following is per user, so the index cannot carry it; the page filters Typesense by these ids.
            'followedInstitutionIds' => $request->user()->followedInstitutions()->pluck('institutions.id')->map(fn ($id): string => (string) $id)->values(),
            'defaultTenantShortnames' => GetUserTenantShortnames::execute($request->user()),
            // Compared with each row's type_ids to offer "Sekti" (InstitutionPolicy::follow).
            'publicMeetingTypeIds' => app(MeetingSettings::class)->getPublicMeetingInstitutionTypeIds()->values(),
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
        $this->handleAuthorization('viewSummary', $institution);

        // An active institution outside the user's reach opens as its public face: overview,
        // members and (public) meetings — no discussion, files, tasks, relations or management.
        $readOnly = ! Gate::allows('view', $institution);

        $institution->load('tenant:id,shortname', 'types', 'duties.current_users', 'checkIns')
            ->loadCount(['comments', 'duties', 'meetings', 'tasks', 'tasksFromMeetings']);
        $showsMeetings = ! $readOnly || $institution->has_public_meetings;

        $institution->append(['has_public_meetings', 'meeting_periodicity_days', 'governance_scope']);
        $activityStatus = $this->activityStatusService->resolve($institution)->toArray();
        $tasksCount = (int) $institution->getAttribute('tasks_count');
        $tasksFromMeetingsCount = (int) $institution->getAttribute('tasks_from_meetings_count');
        $recentComments = $readOnly ? collect() : $institution->comments()
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

        $overviewMeetings = ! $showsMeetings ? collect() : $institution->meetings()
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
        // Offered where following is allowed, or to let an existing follower stop.
        $subscriptionStatus = $user && ($user->can('follow', $institution) || $user->follows($institution)) ? [
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
                'tenant' => $institution->tenant,
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
                'secretaries' => $readOnly ? [] : InstitutionSecretaryController::usersPayload(
                    GetInstitutionSecretaries::execute($institution)
                ),
                'sharepointPath' => $readOnly ? null : SharepointFileService::pathOrNull($institution),
            ],
            'readOnly' => $readOnly,
            'overview' => [
                // The status is read off the meetings, so it is withheld with them.
                'activity_status' => $showsMeetings ? $activityStatus : null,
                'current_users' => $institution->duties->pluck('current_users')->flatten()->unique('id')->values(),
                'duties' => $overviewDuties,
                'recentMeetings' => InstitutionMeetingResource::collection($overviewMeetings)->resolve(),
                'meetings_count' => $showsMeetings ? $institution->meetings_count : 0,
                // Say that meetings exist but are not public, rather than an empty "no meetings".
                'meetings_hidden' => ! $showsMeetings && $institution->meetings_count > 0,
                'recentComments' => $recentComments,
            ],
            'files' => $readOnly ? [] : Inertia::defer(fn () => $institution->availableFiles()->orderByDesc('file_date')->get(), 'files'),
            'typeFiles' => $readOnly ? [] : Inertia::defer(fn () => GetTypeFiles::forFileable($institution), 'files'),
            'duties' => Inertia::defer(fn () => $institution->duties()
                ->with('current_users')
                ->orderBy('order')
                ->get()
                ->toArray(), 'institutionPanels'),
            'meetings' => ! $showsMeetings ? [] : Inertia::defer(fn () => InstitutionMeetingResource::collection(
                $institution->meetings()
                    ->withCount('agendaItems')
                    ->with(['agendaItems.votes', 'fileableFiles', 'institutions.types'])
                    ->orderByDesc('start_time')
                    ->get()
                    ->each->append(['has_report', 'has_protocol'])
            )->resolve(), 'institutionPanels'),
            'tasks' => $readOnly ? [] : Inertia::defer(fn () => TaskResource::collection(
                $institution->tasks()
                    ->with('users:id,name,email,profile_photo_path', 'taskable')
                    ->get()
                    ->merge(Task::query()
                        ->where('taskable_type', MorphMap::alias(Meeting::class))
                        ->whereIn('taskable_id', $institution->meetings()->select('meetings.id'))
                        ->with('users:id,name,email,profile_photo_path', 'taskable')
                        ->get())
                    ->sortByDesc('created_at')
                    ->values()
            )->resolve(), 'institutionPanels'),
            'relatedInstitutions' => $readOnly ? [] : Inertia::defer(fn () => RelationshipService::getRelatedInstitutionsCached($institution)
                ->map(fn (array $item) => [
                    'id' => $item['institution']->id,
                    'name' => $item['institution']->name,
                    'direction' => $item['direction'],
                    'type' => $item['type'],
                    'authorized' => $item['authorized'],
                ])
                ->values()
                ->all(), 'institutionPanels'),
            // Per-record, not from `auth.can`: `institutions.update.padalinys` is tenant-scoped.
            'can' => [
                'update' => $user?->can('update', $institution) ?? false,
                'delete' => $user?->can('delete', $institution) ?? false,
                'recordMeeting' => ! $readOnly && $user !== null && $user->can('create', Meeting::class)
                    && $this->authorizer->tenants($user, 'meetings.create.padalinys')->contains('id', $institution->tenant_id),
                'reportActivity' => ! $readOnly && ($user?->can('create', [InstitutionCheckIn::class, $institution]) ?? false),
            ],
            // Terms and secretary rosters are associations, edited on the record rather than in the
            // form (O22, Forms rule 15). Only someone who may update the institution needs them.
            'management' => Inertia::defer(fn () => $user?->can('update', $institution) ? [
                'cadences' => CadenceController::payload($institution->id),
                'globalCadences' => CadenceController::payload(globalOnly: true),
                'cadenceDefaults' => [
                    'default_start_month_day' => app(CadenceSettings::class)->default_start_month_day,
                    'default_end_month_day' => app(CadenceSettings::class)->default_end_month_day,
                ],
                'secretaryRosters' => InstitutionSecretaryController::payload($institution),
                // Suggested first in the picker: the people already in the body.
                'suggestedSecretaries' => InstitutionSecretaryController::usersPayload(
                    GetInstitutionMembers::execute($institution)
                ),
                // The Priskirti sheet's programme picker, narrowed to this institution's tenant.
                'studyPrograms' => StudyProgram::query()
                    ->where('tenant_id', $institution->tenant_id)
                    ->get(['id', 'name', 'degree', 'tenant_id']),
            ] : null, 'institutionPanels'),
            'subscription' => $subscriptionStatus,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Institution $institution)
    {
        $this->handleAuthorization('update', $institution);

        $institution->load('types');

        Inertia::share('seo.title', $institution->name);

        return $this->inertiaResponse('Admin/People/EditInstitution', [
            'institution' => [
                ...$institution->toFullArray(),
                'types' => $institution->types->pluck('id'),
            ],
            'institutionTypes' => Type::where('model_type', MorphMap::alias(Institution::class))->get(),
            'assignableTenants' => GetTenantsForUpserts::execute('institutions.update.padalinys', $this->authorizer),
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
