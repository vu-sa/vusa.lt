<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MeetingType;
use App\Events\MeetingFullyCreated;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexMeetingRequest;
use App\Http\Requests\StoreMeetingRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Task;
use App\Models\User;
use App\Services\CheckInService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Services\ResourceServices\SharepointFileService;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class MeetingController extends AdminController
{
    use HasTanstackTables;

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

            // Filter by completion status (calculated from agenda items)
            $query->where(function ($q) use ($completionStatuses) {
                foreach ($completionStatuses as $status) {
                    if ($status === 'complete') {
                        // All agenda items have all three fields filled
                        $q->orWhereHas('agendaItems', function ($subQ) {
                            $subQ->whereNotNull('student_vote')
                                ->whereNotNull('decision')
                                ->whereNotNull('student_benefit');
                        }, '=', DB::raw('(SELECT COUNT(*) FROM agenda_items WHERE agenda_items.meeting_id = meetings.id)'))
                            ->whereHas('agendaItems'); // Must have at least one
                    } elseif ($status === 'incomplete') {
                        // Has agenda items but not all are complete
                        $q->orWhere(function ($innerQ) {
                            $innerQ->whereHas('agendaItems')
                                ->whereHas('agendaItems', function ($subQ) {
                                    $subQ->where(function ($itemQ) {
                                        $itemQ->whereNull('student_vote')
                                            ->orWhereNull('decision')
                                            ->orWhereNull('student_benefit');
                                    });
                                });
                        });
                    } elseif ($status === 'no_items') {
                        // No agenda items
                        $q->orWhereDoesntHave('agendaItems');
                    }
                }
            });
        }

        // Apply default sorting if no sorting provided
        if (empty($request->getSorting())) {
            $query->orderBy('start_time', 'desc');
        }

        // Paginate results
        $meetings = $query->paginate($request->input('per_page', 20))
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
            'showDeleted' => $request->boolean('showDeleted', false),
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

            $meeting = Meeting::create([
                'start_time' => $validatedData['start_time'],
                'title' => $title,
                'description' => $validatedData['description'] ?? null,
                'type' => $meetingType,
            ]);

            $meeting->institutions()->attach($validatedData['institution_id']);

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
                        'title' => $agendaItemData['title'],
                        'description' => $agendaItemData['description'] ?? null,
                        'order' => $agendaItemData['order'],
                        'brought_by_students' => $agendaItemData['brought_by_students'] ?? false,
                        'meeting_id' => $meeting->id,
                    ]);
                }
            }

            DB::commit();

            // Dispatch event after meeting is fully set up with all relationships
            event(new MeetingFullyCreated($meeting));

            // For Inertia requests (from modal), redirect to meeting show page
            return redirect()->route('meetings.show', $meeting)->with(['success' => 'Posėdis sukurtas sėkmingai!']);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['general' => $e->getMessage()])->with(['error' => 'Nepavyko sukurti posėdžio.']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Meeting $meeting)
    {
        $this->handleAuthorization('view', $meeting);

        $meeting->load('institutions.types', 'activities.causer', 'fileableFiles', 'comments')->load([
            'tasks' => function ($query) {
                $query->with('users:id,name,email,profile_photo_path', 'taskable');
            },
            'agendaItems' => function ($query) {
                $query->with('votes')->orderBy('order');
            },
        ]);

        // Append is_public, is_joint and file status now that relations are loaded (avoids N+1)
        $meeting->append(['is_public', 'is_joint', 'has_protocol', 'has_report']);

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
                    'type' => class_basename($task->taskable_type),
                ] : null,
                'taskable_type' => class_basename($task->taskable_type ?? ''),
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
                'tasks' => $transformedTasks,
                'sharepointPath' => $meeting->institutions->isNotEmpty() ? SharepointFileService::pathForFileableDriveItem($meeting) : null,
            ],
            'representatives' => $representatives,
            'previousMeeting' => $previousMeeting,
            'nextMeeting' => $nextMeeting,
            'taskableInstitutions' => Inertia::optional(fn () => $meeting->institutions->load('users')),
            'availableInstitutionsForAttach' => $this->getAvailableInstitutionsForAttach($meeting),
        ]);
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
    public function update(Request $request, Meeting $meeting)
    {
        $this->handleAuthorization('update', $meeting);

        $validated = $request->validate([
            // 'title' => 'required|string',
            'start_time' => 'required|date',
            'type' => ['nullable', new Enum(MeetingType::class)],
        ]);

        $validated['title'] = $this->buildMeetingTitle(
            $validated['start_time'],
            $validated['type'] ?? $meeting->type?->value
        );

        $meeting->fill($validated);
        $meeting->save();

        return back()->with('success', 'Posėdis atnaujintas sėkmingai!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting)
    {
        $this->handleAuthorization('delete', $meeting);

        $redirect_url = request()->redirect_to ?? back()->getTargetUrl();

        $meeting->delete();

        return redirect($redirect_url)->with('success', 'Posėdis ištrintas sėkmingai!');
    }

    public function restore(Meeting $meeting)
    {
        $this->handleAuthorization('restore', $meeting);

        $meeting->restore();

        return back()->with('success', 'Posėdis atkurtas!');
    }

    /**
     * Attach an additional institution to a joint meeting.
     */
    public function attachInstitution(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->handleAuthorization('update', $meeting);

        $validated = $request->validate([
            'institution_id' => [
                'required',
                'ulid',
                Rule::exists('institutions', 'id'),
                Rule::notIn($meeting->institutions()->pluck('institutions.id')->all()),
            ],
        ]);

        $meeting->institutions()->attach($validated['institution_id']);

        $institution = Institution::find($validated['institution_id']);
        if ($institution) {
            $this->checkInService->adjustForMeeting($institution, $meeting->start_time);
        }

        return back()->with('success', 'Institucija pridėta.');
    }

    /**
     * Detach an institution from a meeting (joint meeting must keep at least one).
     */
    public function detachInstitution(Meeting $meeting, Institution $institution): RedirectResponse
    {
        $this->handleAuthorization('update', $meeting);

        if ($meeting->institutions()->count() <= 1) {
            return back()->with('error', 'Posėdis turi turėti bent vieną instituciją.');
        }

        $meeting->institutions()->detach($institution->id);

        return back()->with('success', 'Institucija pašalinta.');
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
     * Build the auto-generated meeting title.
     *
     * Email-type meetings use a 23:59 deadline marker for `start_time`, so the
     * time portion is intentionally omitted from the title to avoid showing a
     * misleading "23.59 val." in the UI.
     */
    private function buildMeetingTitle(mixed $startTime, mixed $type): string
    {
        $typeValue = $type instanceof MeetingType ? $type->value : $type;
        $isEmail = $typeValue === MeetingType::Email->value;

        $format = $isEmail
            ? 'YYYY MMMM DD [d.]'
            : 'YYYY MMMM DD [d.] HH.mm [val.]';

        return Carbon::parse($startTime)->locale('lt-LT')->isoFormat($format).' posėdis';
    }
}
