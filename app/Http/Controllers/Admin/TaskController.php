<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTaskSummaryRequest;
use App\Http\Requests\IndexUserTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Support\MorphMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class TaskController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the current user's own tasks.
     */
    public function index(IndexUserTasksRequest $request)
    {
        $user = User::find(Auth::id());

        $tasksQuery = $user->tasks()->with('taskable', 'users:id,name,email,profile_photo_path', 'tenants');

        // Get task statistics (before any filtering for accurate counts)
        $taskStats = [
            'total' => (clone $tasksQuery)->whereNull('completed_at')->count(),
            'completed' => (clone $tasksQuery)->whereNotNull('completed_at')->count(),
            'overdue' => (clone $tasksQuery)->whereNull('completed_at')->where('due_date', '<', now())->count(),
            'autoCompleting' => (clone $tasksQuery)->whereNull('completed_at')->whereNotNull('action_type')->where('action_type', '!=', 'manual')->count(),
        ];

        // Apply status filter
        $status = $request->input('status', 'incomplete');

        match ($status) {
            'completed' => $tasksQuery->whereNotNull('completed_at'),
            'incomplete' => $tasksQuery->whereNull('completed_at'),
            default => null, // 'all' - no filter
        };

        // Apply ordering based on status filter
        if ($status === 'completed') {
            // Completed tasks: most recently completed first
            $tasksQuery->latest('completed_at');
        } else {
            // Incomplete/all: overdue first, then by due date, then by creation date
            $tasksQuery
                // Put incomplete tasks first when showing all
                ->orderByRaw('completed_at IS NOT NULL')
                ->orderByRaw('CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 0 ELSE 1 END', [now()])
                ->orderBy('due_date')
                ->latest('created_at');
        }

        // Paginate tasks
        $perPage = $request->getPerPage();
        $paginatedTasks = $tasksQuery->paginate($perPage)->withQueryString();

        $tasks = $paginatedTasks->through(fn ($task) => [
            ...new TaskResource($task)->resolve(),
            'can_delete' => $task->isDeletableBy($user),
        ]);

        return $this->inertiaResponse('Admin/ShowTasks', [
            'tasks' => $tasks,
            'taskStats' => $taskStats,
            'status' => $status,
        ]);
    }

    /**
     * Return tasks for the current user in JSON format.
     * Used by the TasksIndicator component.
     *
     * @return JsonResponse
     */
    public function userTasksForIndicator(Request $request)
    {
        $limit = $request->input('limit', 5);

        $tasks = Task::with('taskable')
            ->whereHas('users', function ($query): void {
                $query->where('users.id', Auth::id());
            })
            ->whereNull('completed_at')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $validatedData = $request->safe();
        $taskData = [
            'name' => $validatedData['name'],
            'taskable_id' => $validatedData['taskable_id'],
            'taskable_type' => $validatedData['taskable_type'],
            'due_date' => $validatedData['due_date'],
        ];

        // if separate_tasks is true, create separate tasks for each responsible person
        if ($request->separate_tasks) {
            foreach ($request->responsible_people as $responsible_person) {
                $task = Task::create($taskData);
                $task->users()->attach($responsible_person);
            }
        } else {
            $task = Task::create($taskData);
            $task->users()->attach($request->responsible_people);
        }

        return back()->with('success', $this->entityMessage('created', 'task'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->handleAuthorization('update', $task);

        $validated = $request->validated();

        // change due_date to Carbon object
        $validated['due_date'] = Carbon::createFromTimestamp($validated['due_date'] / 1000, 'Europe/Vilnius');

        $task->update($validated);

        return back()->with('success', $this->entityMessage('updated', 'task'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $this->handleAuthorization('delete', $task);

        // An automatic task is normally the system's to close, not a person's. Super admins are
        // the exception: some end up unclosable (the agenda can no longer be completed, the
        // subject is gone) and there has to be a way out.
        if (! $task->canBeManuallyCompleted() && ! Auth::user()->isSuperAdmin()) {
            return back()->with('error', __('messages.task.automatic_not_deletable'));
        }

        $task->delete();

        return back()->with('success', $this->entityMessage('deleted', 'task'));
    }

    public function updateCompletionStatus(Request $request, Task $task)
    {
        $this->handleAuthorization('update', $task);

        // Prevent manual completion of auto-completing tasks
        if (! $task->canBeManuallyCompleted()) {
            return back()->with('error', __('messages.task.automatic_not_markable'));
        }

        if ($request->completed == true) {
            $task->completed_at = now();
        } else {
            $task->completed_at = null;
        }

        $task->save();

        return back()->with('success', __('messages.task.status_updated'));
    }

    /**
     * Display a summary of tasks across the user's accessible tenants.
     * Requires tasks.read.padalinys permission and compound authorization
     * with the related taskable models.
     *
     * @return Response
     */
    public function summary(IndexTaskSummaryRequest $request)
    {
        $this->handleAuthorization('viewAny', Task::class);

        $user = Auth::user();

        // Get user's accessible tenants for tasks
        $taskPermissibleTenants = $this->authorizer->tenants($user, 'tasks.read.padalinys');

        // Get user's accessible tenants for meetings, reservations, and institutions
        $meetingPermissibleTenants = $this->authorizer->tenants($user, 'meetings.read.padalinys');
        $reservationPermissibleTenants = $this->authorizer->tenants($user, 'reservations.read.padalinys');
        $institutionPermissibleTenants = $this->authorizer->tenants($user, 'institutions.read.padalinys');

        // Build base query with compound authorization
        $baseQuery = Task::with(['users:id,name,email,profile_photo_path', 'taskable', 'tenants'])
            ->whereHas('tenants', function ($q) use ($taskPermissibleTenants): void {
                $q->whereIn('tenants.id', $taskPermissibleTenants->pluck('id'));
            });

        // Apply compound authorization: only show tasks where user also has permission on taskable
        $baseQuery->where(function ($q) use ($meetingPermissibleTenants, $reservationPermissibleTenants, $institutionPermissibleTenants): void {
            // Meeting tasks - user must have meetings.read.padalinys
            if ($meetingPermissibleTenants->isNotEmpty()) {
                $q->orWhere(function ($subQ) use ($meetingPermissibleTenants): void {
                    $subQ->where('taskable_type', MorphMap::alias(Meeting::class))
                        ->whereHasMorph('taskable', [Meeting::class], function ($meetingQ) use ($meetingPermissibleTenants): void {
                            $meetingQ->whereHas('tenants', function ($tenantQ) use ($meetingPermissibleTenants): void {
                                $tenantQ->whereIn('tenants.id', $meetingPermissibleTenants->pluck('id'));
                            });
                        });
                });
            }

            // Reservation tasks - user must have reservations.read.padalinys
            if ($reservationPermissibleTenants->isNotEmpty()) {
                $q->orWhere(function ($subQ) use ($reservationPermissibleTenants): void {
                    $subQ->where('taskable_type', MorphMap::alias(Reservation::class))
                        ->whereHasMorph('taskable', [Reservation::class], function ($reservationQ) use ($reservationPermissibleTenants): void {
                            $reservationQ->whereHas('tenants', function ($tenantQ) use ($reservationPermissibleTenants): void {
                                $tenantQ->whereIn('tenants.id', $reservationPermissibleTenants->pluck('id'));
                            });
                        });
                });
            }

            // A task outlives a hard-deleted subject. There is nothing left to compound-authorize
            // against, and the tenant filter above already scopes it, so surface it rather than
            // hiding the one listing from which such a task could ever be cleared away.
            $q->orWhere(function ($subQ): void {
                $subQ->whereDoesntHaveMorph('taskable', Task::TASKABLE_TYPES);
            });

            // Institution tasks (e.g., PeriodicityGap) - user must have institutions.read.padalinys
            if ($institutionPermissibleTenants->isNotEmpty()) {
                $q->orWhere(function ($subQ) use ($institutionPermissibleTenants): void {
                    $subQ->where('taskable_type', MorphMap::alias(Institution::class))
                        ->whereHasMorph('taskable', [Institution::class], function ($institutionQ) use ($institutionPermissibleTenants): void {
                            $institutionQ->whereHas('tenant', function ($tenantQ) use ($institutionPermissibleTenants): void {
                                $tenantQ->whereIn('tenants.id', $institutionPermissibleTenants->pluck('id'));
                            });
                        });
                });
            }
        });

        // Filter by tenant if specified
        $tenantIds = $request->input('tenant_ids', []);
        if (! empty($tenantIds)) {
            $baseQuery->whereHas('tenants', function ($q) use ($tenantIds): void {
                $q->whereIn('tenants.id', $tenantIds);
            });
        }

        // Clone query for stats calculation BEFORE type filter is applied
        $statsQuery = clone $baseQuery;

        // Calculate stats using efficient database aggregations
        $total = (clone $statsQuery)->whereNull('completed_at')->count();
        $completed = (clone $statsQuery)->whereNotNull('completed_at')->count();

        // For overdue count, we need a simpler approach - overdue = pending + has due_date in past
        $overdue = (clone $statsQuery)
            ->whereNull('completed_at')
            ->where('due_date', '<', now())
            ->count();

        // Auto-completing: tasks with certain action types that auto-complete
        $autoCompletable = (clone $statsQuery)
            ->whereNull('completed_at')
            ->whereIn('action_type', ['approval', 'pickup', 'return'])
            ->count();

        // Type counts using direct database queries, one per real taskable type — the frontend's
        // filter chips toggle these independently rather than through a merged "institutions"
        // group, so a periodicity-gap task (taskable=institution) and an agenda task
        // (taskable=meeting) can be selected together or apart.
        $institutionCount = (clone $statsQuery)->where('taskable_type', MorphMap::alias(Institution::class))->count();
        $meetingCount = (clone $statsQuery)->where('taskable_type', MorphMap::alias(Meeting::class))->count();
        $reservationCount = (clone $statsQuery)->where('taskable_type', MorphMap::alias(Reservation::class))->count();

        $taskStats = [
            'total' => $total,
            'completed' => $completed,
            'overdue' => $overdue,
            'autoCompleting' => $autoCompletable,
            'byType' => [
                'institution' => $institutionCount,
                'meeting' => $meetingCount,
                'reservation' => $reservationCount,
            ],
        ];

        // Now apply the type filter for the paginated results. `taskable_type` is normalized to
        // an array by IndexTaskSummaryRequest::prepareForValidation(), and its values are already
        // the raw `taskable_type` column aliases (Task::TASKABLE_TYPES), so no MorphMap lookup
        // is needed here.
        $taskableTypes = $request->validated('taskable_type') ?? [];
        if (! empty($taskableTypes)) {
            $baseQuery->whereIn('taskable_type', $taskableTypes);
        }

        // Apply completion filter. Pending by default: the summary is an action list, and the
        // page used to reach the same result by discarding completed rows from whichever page it
        // had been handed — which quietly disagreed with the paginator's own counts.
        $completionFilter = $request->input('completion', 'pending');
        if ($completionFilter === 'pending') {
            $baseQuery->whereNull('completed_at');
        } elseif ($completionFilter === 'completed') {
            $baseQuery->whereNotNull('completed_at');
        }

        // Order by due date and completion
        $baseQuery->orderBy('completed_at', 'asc')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc');

        // Paginate results
        $tasks = $baseQuery->paginate($request->getPerPage())
            ->withQueryString();

        $transformedTasks = $tasks->getCollection()->map(fn (Task $task) => [
            ...new TaskResource($task)->resolve(),
            'can_delete' => $task->isDeletableBy($user),
        ]);

        return $this->inertiaResponse('Admin/ShowTasksSummary', [
            'tasks' => [
                'data' => $transformedTasks,
                'meta' => [
                    'total' => $tasks->total(),
                    'per_page' => $tasks->perPage(),
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem(),
                ],
            ],
            'taskStats' => $taskStats,
            'filters' => [
                'taskable_type' => $taskableTypes,
                'completion' => $completionFilter,
                'tenant_ids' => $tenantIds,
            ],
            'permissibleTenants' => $taskPermissibleTenants->map(fn ($t) => [
                'id' => $t->id,
                'shortname' => $t->shortname,
            ]),
        ]);
    }
}
