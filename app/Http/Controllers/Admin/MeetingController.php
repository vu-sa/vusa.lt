<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AnnounceMeetingInCalendar;
use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Events\MeetingFullyCreated;
use App\Http\Controllers\AdminController;
use App\Http\Requests\AttachMeetingInstitutionRequest;
use App\Http\Requests\IndexMeetingRequest;
use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Task;
use App\Models\User;
use App\Services\CheckInService;
use App\Services\InstitutionScopeResolver;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Services\ResourceServices\SharepointFileService;
use App\Services\TanstackTableService;
use App\Support\MeetingTitle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MeetingController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(
        public Authorizer $authorizer,
        private CheckInService $checkInService,
        private TanstackTableService $tableService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexMeetingRequest $request)
    {
        $this->handleAuthorization('viewAny', Meeting::class);

        // Build base query with eager loading
        $query = Meeting::query()->with(['institutions.tenant', 'agendaItems', 'fileableFiles']);

        // Apply permission filtering based on user's permissible tenants
        $query = $this->tableService->applyPermissionFiltering(
            $query,
            'tenants',
            'meetings.read.padalinys',
            $this->authorizer
        );

        // Define searchable columns
        $searchableColumns = ['title', 'description'];

        // Apply Tanstack Table filters
        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        // Apply manual completion status filter if provided
        $filters = $request->getFilters();
        if (isset($filters['completion_status']) && ! empty($filters['completion_status'])) {
            $completionStatuses = is_array($filters['completion_status'])
                ? $filters['completion_status']
                : [$filters['completion_status']];

            $externalTypeIds = app(InstitutionScopeResolver::class)->typeIdsResolvingExternal();
            $incomplete = fn ($itemQuery) => $this->incompleteAgendaItem($itemQuery, $externalTypeIds);

            $query->where(function ($q) use ($completionStatuses, $incomplete): void {
                foreach ($completionStatuses as $status) {
                    match ($status) {
                        'complete' => $q->orWhere(fn ($inner) => $inner
                            ->whereHas('agendaItems')
                            ->whereDoesntHave('agendaItems', $incomplete)),
                        'incomplete' => $q->orWhereHas('agendaItems', $incomplete),
                        'no_items' => $q->orWhereDoesntHave('agendaItems'),
                        default => null,
                    };
                }
            });
        }

        // Apply default sorting if no sorting provided
        if (empty($request->getSorting())) {
            $query->orderBy('start_time', 'desc');
        }

        // Paginate results
        $deletedCount = $this->getTrashedCount($query);

        $meetings = $query->paginate($request->getPerPage())
            ->withQueryString();

        // Append file status attributes for badge display
        $meetings->getCollection()->each(fn ($meeting) => $meeting->append(['has_protocol', 'has_report']));

        // Get the sorting state
        $sorting = $request->getSorting();

        // Return response with all necessary data
        return $this->inertiaResponse('Admin/Representation/IndexMeeting', [
            'data' => $meetings->items(),
            'meta' => [
                'total' => $meetings->total(),
                'per_page' => $meetings->perPage(),
                'current_page' => $meetings->currentPage(),
                'last_page' => $meetings->lastPage(),
                'from' => $meetings->firstItem(),
                'to' => $meetings->lastItem(),
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
        ]);
    }

    /**
     * Display the Typesense-powered search page for meetings and agenda items.
     *
     * This page uses scoped API keys for authorization - the search key
     * has tenant filtering embedded, ensuring users can only see meetings
     * they have permission to access.
     */
    public function search()
    {
        $this->handleAuthorization('viewAny', Meeting::class);

        return Inertia::render('Admin/Representation/SearchMeetings');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMeetingRequest $request)
    {
        $validatedData = $request->safe();

        DB::beginTransaction();

        try {
            $meetingType = $validatedData['type'] ?? null;
            $title = $this->buildMeetingTitle($validatedData['start_time'], $meetingType);

            // Created from an existing announcement: the meeting takes over the event's
            // timing rather than leaving the pair to drift (Meeting::syncCalendarEventTiming
            // then pushes it back, so both ends have to agree from the start).
            $announcement = isset($validatedData['calendar_id'])
                ? Calendar::query()->whereNull('meeting_id')->find($validatedData['calendar_id'])
                : null;

            $meeting = Meeting::create([
                'start_time' => $validatedData['start_time'],
                'title' => $title,
                'description' => $validatedData['description'] ?? null,
                'type' => $meetingType,
                'end_time' => $announcement?->end_date,
            ]);

            if ($announcement !== null) {
                $announcement->meeting_id = $meeting->id;
                // From here on the meeting owns the timing (Meeting::syncCalendarEventTiming),
                // so the announcement adopts it now rather than drifting until the next edit —
                // an email meeting, for instance, moves its start to a 23:59 deadline.
                $announcement->date = $meeting->start_time;
                $announcement->end_date = $meeting->end_time;
                $announcement->save();
            }

            $meeting->attachAudited('institutions', $validatedData['institution_id']);

            // Adjust any overlapping check-ins for this institution
            $institution = Institution::find($validatedData['institution_id']);
            if ($institution) {
                $meetingDate = Carbon::parse($validatedData['start_time']);
                $this->checkInService->adjustForMeeting($institution, $meetingDate);
            }

            // Create agenda items if provided
            if (isset($validatedData['agendaItems']) && is_array($validatedData['agendaItems'])) {
                foreach ($validatedData['agendaItems'] as $agendaItemData) {
                    AgendaItem::create([
                        'title' => ['lt' => $agendaItemData['title']],
                        'description' => isset($agendaItemData['description'])
                            ? ['lt' => $agendaItemData['description']]
                            : null,
                        'order' => $agendaItemData['order'],
                        'brought_by_students' => $agendaItemData['brought_by_students'] ?? false,
                        'start_time' => $agendaItemData['start_time'] ?? null,
                        'end_time' => $agendaItemData['end_time'] ?? null,
                        'meeting_id' => $meeting->id,
                    ]);
                }
            }

            if ($validatedData['announce_in_calendar'] ?? false) {
                AnnounceMeetingInCalendar::execute($meeting);
            }

            DB::commit();

            // Dispatch event after meeting is fully set up with all relationships
            event(new MeetingFullyCreated($meeting));

            // For Inertia requests (from modal), redirect to meeting show page. The action
            // window can ask to land straight in the bulk agenda dialog, which the show
            // page opens from `?action=add-bulk`.
            $parameters = ['meeting' => $meeting];

            if ($validatedData['open_bulk_agenda'] ?? false) {
                $parameters['action'] = 'add-bulk';
            }

            return redirect()->route('meetings.show', $parameters)->with(['success' => __('messages.meeting.created')]);

        } catch (\Throwable $e) {
            // \Throwable, not \Exception: a \TypeError (or any other \Error) between
            // beginTransaction() and commit() must still roll back, or the transaction depth
            // leaks for the rest of the process.
            DB::rollBack();

            return back()->withErrors(['general' => $e->getMessage()])->with(['error' => __('messages.meeting.create_failed')]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Meeting $meeting)
    {
        $this->handleAuthorization('view', $meeting);

        $meeting->load('institutions.types', 'institutions.tenant', 'fileableFiles', 'comments', 'calendarEvent')->load([
            'tasks' => function ($query): void {
                $query->with('users:id,name,email,profile_photo_path', 'taskable');
            },
            'agendaItems' => function ($query): void {
                $query->with('votes')->withCount('comments')
                    ->withExists(['note as has_notes' => fn ($note) => $note->whereNotNull('notes_html')])
                    ->orderBy('order');
            },
            'documents' => function ($query): void {
                $query->orderBy('document_date')->orderBy('title');
            },
        ])->loadCount('comments');

        // Append is_public, is_joint and file status now that relations are loaded (avoids N+1)
        $meeting->append(['is_public', 'is_joint', 'has_protocol', 'has_report']);

        // Derived, so it has to be appended to reach the panel that labels each document
        // by language.
        $meeting->documents->each->append('language_code');

        // Transform tasks with computed properties (same as userTasks method)
        $transformedTasks = $meeting->tasks->map(function (Task $task, int $key) {
            /** @var Model|null $taskable */
            $taskable = $task->taskable;

            return [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'due_date' => $task->due_date?->toISOString(),
                'completed_at' => $task->completed_at?->toISOString(),
                'created_at' => $task->created_at->toISOString(),
                'action_type' => $task->action_type?->value,
                'metadata' => $task->metadata,
                'progress' => $task->getProgress(),
                'is_overdue' => $task->isOverdue(),
                'can_be_manually_completed' => $task->canBeManuallyCompleted(),
                'icon' => $task->icon,
                'color' => $task->color,
                'taskable' => $taskable ? [
                    'id' => $taskable->getKey(),
                    'name' => $taskable->getAttribute('title') ?? $taskable->getAttribute('name') ?? null,
                    'type' => $task->taskable_type,
                ] : null,
                'taskable_type' => $task->taskable_type ?? '',
                'taskable_id' => $task->taskable_id,
                'users' => $task->users->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'profile_photo_path' => $u->profile_photo_path,
                ])->all(),
            ];
        });

        // Get representatives who were active at meeting time
        $representatives = $meeting->getRepresentativesActiveAt();

        // Get primary institution for navigation
        $primaryInstitution = $meeting->institutions->first();

        // Get previous and next meetings for the same institution
        $previousMeeting = null;
        $nextMeeting = null;

        if ($primaryInstitution) {
            $previousMeeting = Meeting::query()
                ->whereHas('institutions', fn ($q) => $q->where('institutions.id', $primaryInstitution->id))
                ->where('start_time', '<', $meeting->start_time)
                ->orderBy('start_time', 'desc')
                ->select(['id', 'start_time', 'type'])
                ->first();

            $nextMeeting = Meeting::query()
                ->whereHas('institutions', fn ($q) => $q->where('institutions.id', $primaryInstitution->id))
                ->where('start_time', '>', $meeting->start_time)
                ->orderBy('start_time', 'asc')
                ->select(['id', 'start_time', 'type'])
                ->first();
        }

        // show meeting
        return $this->inertiaResponse('Admin/Representation/ShowMeeting', [
            'meeting' => [
                ...$meeting->toArray(),
                // The edit dialog writes the description, so it needs every locale rather
                // than the current one — the rest of the page reads the localized array above.
                'description' => $meeting->getTranslations('description'),
                'tasks' => $transformedTasks,
                'sharepointPath' => $meeting->institutions->isNotEmpty() ? SharepointFileService::pathForFileableDriveItem($meeting) : null,
            ],
            'representatives' => $representatives,
            // Nominated for the term the meeting fell in. When present, these are the
            // people the agenda tasks went to instead of the whole membership.
            'administrators' => InstitutionAdministratorController::forMeetingPayload($meeting),
            'previousMeeting' => $previousMeeting,
            'nextMeeting' => $nextMeeting,
            'taskableInstitutions' => Inertia::optional(fn () => $meeting->institutions->load('users')),
            'availableInstitutionsForAttach' => $this->getAvailableInstitutionsForAttach($meeting),
            'governanceScope' => $this->governanceScopeFor($meeting),
        ]);
    }

    /**
     * An agenda item that still needs data entered.
     *
     * The vote fields live on `votes`, not on `agenda_items` — they moved there in
     * 2026_01_23_221740 and this filter went on querying the dropped columns, so any request
     * using it threw. `student_vote` / `student_benefit` are only demanded of external bodies,
     * matching MeetingCompletionService.
     *
     * @param  Builder<AgendaItem>  $query
     * @param  array<int, int>  $externalTypeIds
     */
    private function incompleteAgendaItem($query, array $externalTypeIds): void
    {
        // `type` is nullable and a NULL type still needs filling in, but SQL's `!=` drops NULLs.
        $query->where(fn ($typeQuery) => $typeQuery
            ->whereNull('type')
            ->orWhereNotIn('type', AgendaItemType::voteFreeValues()))
            ->where(function ($itemQuery) use ($externalTypeIds): void {
                $itemQuery
                    // Covers both "no votes at all" and "no vote carrying an outcome".
                    ->whereDoesntHave('votes', fn ($voteQuery) => $voteQuery
                        ->whereNotNull('decision')->where('decision', '!=', ''))
                    ->orWhere(function ($external) use ($externalTypeIds): void {
                        $external
                            ->where(fn ($scopeQuery) => $scopeQuery
                                ->whereHas('meeting.institutions.types', fn ($typeQuery) => $typeQuery
                                    ->whereIn('types.id', $externalTypeIds))
                                ->orWhereDoesntHave('meeting.institutions.types'))
                            ->whereDoesntHave('votes', fn ($voteQuery) => $voteQuery
                                ->whereNotNull('student_vote')->where('student_vote', '!=', '')
                                ->whereNotNull('student_benefit')->where('student_benefit', '!=', ''));
                    });
            });
    }

    /**
     * The scope that decides which vote fields this meeting asks for.
     *
     * A joint VU/VU SA meeting keeps the student perspective, so an external institution wins.
     */
    private function governanceScopeFor(Meeting $meeting): string
    {
        $scopes = $meeting->institutions->map(fn (Institution $institution) => $institution->governance_scope);

        $external = $scopes->first(fn (InstitutionScope $scope) => $scope->isExternal());

        return ($external ?? $scopes->first() ?? InstitutionScopeResolver::DEFAULT)->value;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Meeting $meeting)
    {
        $this->handleAuthorization('update', $meeting);

        return $this->inertiaResponse('Admin/Representation/EditMeeting', [
            'meeting' => $meeting,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMeetingRequest $request, Meeting $meeting)
    {
        $this->handleAuthorization('update', $meeting);

        $validated = $request->validated();

        $validated['title'] = $this->buildMeetingTitle(
            $validated['start_time'],
            $validated['type'] ?? $meeting->type?->value
        );

        $meeting->fill($validated);
        $meeting->save();

        return back()->with('success', __('messages.meeting.updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting)
    {
        $this->handleAuthorization('delete', $meeting);

        $redirect_url = request()->redirect_to ?? back()->getTargetUrl();

        $meeting->delete();

        return redirect($redirect_url)->with('success', __('messages.meeting.deleted'));
    }

    public function restore(Meeting $meeting): RedirectResponse
    {
        return $this->restoreModel($meeting, __('messages.meeting.restored'));
    }

    /**
     * Attach an additional institution to a joint meeting.
     */
    public function attachInstitution(AttachMeetingInstitutionRequest $request, Meeting $meeting): RedirectResponse
    {
        $this->handleAuthorization('update', $meeting);

        $validated = $request->validated();

        $meeting->attachAudited('institutions', $validated['institution_id']);

        $institution = Institution::find($validated['institution_id']);
        if ($institution) {
            $this->checkInService->adjustForMeeting($institution, $meeting->start_time);
        }

        return back()->with('success', __('messages.meeting.institution_attached'));
    }

    /**
     * Detach an institution from a meeting (joint meeting must keep at least one).
     */
    public function detachInstitution(Meeting $meeting, Institution $institution): RedirectResponse
    {
        $this->handleAuthorization('update', $meeting);

        if ($meeting->institutions()->count() <= 1) {
            return back()->with('error', __('messages.meeting.institution_required'));
        }

        $meeting->detachAudited('institutions', $institution->id);

        return back()->with('success', __('messages.meeting.institution_detached'));
    }

    /**
     * Build the list of institutions the current user can attach to this meeting.
     * Includes the user's own duty institutions plus institutions related to them
     * via the relationship graph, minus those already attached to the meeting.
     */
    private function getAvailableInstitutionsForAttach(Meeting $meeting): Collection
    {
        $user = auth()->user();
        $userInstitutionIds = $user->loadMissing('current_duties')
            ->current_duties
            ->pluck('institution_id')
            ->filter()
            ->unique();

        $userInstitutions = Institution::whereIn('id', $userInstitutionIds)->get();

        $relatedIds = collect();
        foreach ($userInstitutions as $institution) {
            foreach (RelationshipService::getRelatedInstitutionsCached($institution) as $item) {
                $relatedIds->push($item['institution']->id);
            }
        }

        $attachedIds = $meeting->institutions->pluck('id')->toArray();

        $allAvailableIds = $userInstitutionIds
            ->merge($relatedIds)
            ->unique()
            ->diff($attachedIds)
            ->values();

        if ($allAvailableIds->isEmpty()) {
            return collect();
        }

        return Institution::whereIn('id', $allAvailableIds)
            ->with('tenant:id,shortname')
            ->get()
            ->map(fn (Institution $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'tenant_shortname' => $i->tenant?->shortname,
            ]);
    }

    /**
     * The stored title is always Lithuanian; {@see MeetingTitle} renders the other locale
     * on the public page, so a locale change never needs a data backfill.
     */
    private function buildMeetingTitle(mixed $startTime, mixed $type): string
    {
        return MeetingTitle::build($startTime, $type, 'lt');
    }

    public function forceDelete(Meeting $meeting): RedirectResponse
    {
        return $this->forceDeleteModel($meeting);
    }
}
