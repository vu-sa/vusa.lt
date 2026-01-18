<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TaskController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of tasks.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $tasks = Task::with(['users', 'taskable'])
            ->whereHas('users', function ($query) {
                $query->where('users.id', Auth::id());
            })
            ->orderBy('completed_at', 'asc')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->inertiaResponse('Admin/ShowTasks', [
            'tasks' => $tasks,
        ]);
    }

    /**
     * Return tasks for the current user in JSON format.
     * Used by the TasksIndicator component.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userTasksForIndicator(Request $request)
    {
        $limit = $request->input('limit', 5);

        $tasks = Task::with('taskable')
            ->whereHas('users', function ($query) {
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

        return back()->with('success', 'Užduotis sėkmingai pridėta');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $this->handleAuthorization('update', $task);

        $validated = $request->validate([
            'name' => 'required',
            'due_date' => 'required',
        ]);

        // change due_date to Carbon object
        $validated['due_date'] = Carbon::createFromTimestamp($validated['due_date'] / 1000, 'Europe/Vilnius');

        $task->update($validated);

        return back()->with('success', 'Užduotis sėkmingai atnaujinta');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $this->handleAuthorization('delete', $task);

        // Prevent deletion of auto-completing tasks
        if (! $task->canBeManuallyCompleted()) {
            return back()->with('error', 'Ši užduotis užsibaigia automatiškai ir negali būti ištrinta');
        }

        $task->delete();

        return back()->with('success', 'Užduotis sėkmingai ištrinta');
    }

    public function updateCompletionStatus(Request $request, Task $task)
    {
        $this->handleAuthorization('update', $task);

        // Prevent manual completion of auto-completing tasks
        if (! $task->canBeManuallyCompleted()) {
            return back()->with('error', 'Ši užduotis užsibaigia automatiškai ir negali būti pažymėta rankiniu būdu');
        }

        if ($request->completed == true) {
            $task->completed_at = now();
        } else {
            $task->completed_at = null;
        }

        $task->save();

        return back()->with('success', 'Užduoties būsena sėkmingai atnaujinta');
    }

    /**
     * Display a summary of tasks across the user's accessible tenants.
     * Requires tasks.read.padalinys permission and compound authorization
     * with the related taskable models.
     *
     * @return \Inertia\Response
     */
    public function summary(Request $request)
    {
        $this->handleAuthorization('viewAny', Task::class);

        $user = Auth::user();
        $this->authorizer->forUser($user);

        // Get user's accessible tenants for tasks
        $taskPermissibleTenants = $this->authorizer->getTenants('tasks.read.padalinys');

        // Get user's accessible tenants for meetings and reservations
        $meetingPermissibleTenants = $this->authorizer->getTenants('meetings.read.padalinys');
        $reservationPermissibleTenants = $this->authorizer->getTenants('reservations.read.padalinys');

        // Build base query with compound authorization
        $baseQuery = Task::with(['users:id,name,email,profile_photo_path', 'taskable'])
            ->whereHas('tenants', function ($q) use ($taskPermissibleTenants) {
                $q->whereIn('tenants.id', $taskPermissibleTenants->pluck('id'));
            });

        // Apply compound authorization: only show tasks where user also has permission on taskable
        $baseQuery->where(function ($q) use ($meetingPermissibleTenants, $reservationPermissibleTenants) {
            // Meeting tasks - user must have meetings.read.padalinys
            if ($meetingPermissibleTenants->isNotEmpty()) {
                $q->orWhere(function ($subQ) use ($meetingPermissibleTenants) {
                    $subQ->where('taskable_type', \App\Models\Meeting::class)
                        ->whereHasMorph('taskable', [\App\Models\Meeting::class], function ($meetingQ) use ($meetingPermissibleTenants) {
                            $meetingQ->whereHas('tenants', function ($tenantQ) use ($meetingPermissibleTenants) {
                                $tenantQ->whereIn('tenants.id', $meetingPermissibleTenants->pluck('id'));
                            });
                        });
                });
            }

            // Reservation tasks - user must have reservations.read.padalinys
            if ($reservationPermissibleTenants->isNotEmpty()) {
                $q->orWhere(function ($subQ) use ($reservationPermissibleTenants) {
                    $subQ->where('taskable_type', \App\Models\Reservation::class)
                        ->whereHasMorph('taskable', [\App\Models\Reservation::class], function ($reservationQ) use ($reservationPermissibleTenants) {
                            $reservationQ->whereHas('tenants', function ($tenantQ) use ($reservationPermissibleTenants) {
                                $tenantQ->whereIn('tenants.id', $reservationPermissibleTenants->pluck('id'));
                            });
                        });
                });
            }
        });

        // Filter by tenant if specified
        $tenantIds = $request->input('tenant_ids', []);
        if (! empty($tenantIds)) {
            $baseQuery->whereHas('tenants', function ($q) use ($tenantIds) {
                $q->whereIn('tenants.id', $tenantIds);
            });
        }

        // Clone query for stats calculation BEFORE type filter is applied
        $statsQuery = clone $baseQuery;

        // Calculate stats from unfiltered query (for byType counts that persist)
        $allTasks = $statsQuery->get();
        $taskStats = [
            'total' => $allTasks->whereNull('completed_at')->count(),
            'completed' => $allTasks->whereNotNull('completed_at')->count(),
            'overdue' => $allTasks->filter(fn ($t) => $t->isOverdue())->count(),
            'autoCompleting' => $allTasks->whereNull('completed_at')->filter(fn ($t) => $t->isAutoCompletable())->count(),
            'byType' => [
                'meetings' => $allTasks->where('taskable_type', \App\Models\Meeting::class)->count(),
                'reservations' => $allTasks->where('taskable_type', \App\Models\Reservation::class)->count(),
            ],
        ];

        // Now apply type filter for the paginated results
        $taskableType = $request->input('taskable_type');
        if ($taskableType) {
            $baseQuery->where('taskable_type', $taskableType);
        }

        // Apply completion filter
        $completionFilter = $request->input('completion');
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
        $tasks = $baseQuery->paginate($request->input('per_page', 20))
            ->withQueryString();

        // Transform tasks for frontend
        $transformedTasks = $tasks->getCollection()->map(fn ($task) => [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'due_date' => $task->due_date?->toISOString(),
            'completed_at' => $task->completed_at?->toISOString(),
            'created_at' => $task->created_at?->toISOString(),
            'action_type' => $task->action_type?->value,
            'metadata' => $task->metadata,
            'progress' => $task->getProgress(),
            'is_overdue' => $task->isOverdue(),
            'can_be_manually_completed' => $task->canBeManuallyCompleted(),
            'icon' => $task->icon,
            'color' => $task->color,
            'taskable' => $task->taskable ? [
                'id' => $task->taskable->id,
                'name' => $task->taskable->title ?? $task->taskable->name ?? null,
                'type' => class_basename($task->taskable_type),
            ] : null,
            'taskable_type' => class_basename($task->taskable_type ?? ''),
            'taskable_id' => $task->taskable_id,
            'users' => $task->users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'profile_photo_path' => $u->profile_photo_path,
            ]),
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
                'taskable_type' => $taskableType,
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
