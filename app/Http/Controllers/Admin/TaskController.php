<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BuildTaskIndexQuery;
use App\Events\TaskCreated;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class TaskController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Mano → Užduotys: the tasks assigned to the current user.
     */
    public function index(IndexTasksRequest $request): Response
    {
        return $this->renderCollection($request, BuildTaskIndexQuery::SCOPE_MINE);
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
        $people = $validatedData['responsible_people'] ?? [];
        $assigneeGroups = $request->boolean('separate_tasks')
            ? array_map(fn ($person): array => [$person], $people)
            : [$people];

        foreach ($assigneeGroups as $assignees) {
            $task = Task::create($taskData);
            $task->users()->attach($assignees);

            event(new TaskCreated($task, $request->user()));
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
     * ViSAK → Užduotys: every task in the padaliniai the user may read, on the same page as
     * their own list.
     */
    public function summary(IndexTasksRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Task::class);

        return $this->renderCollection($request, BuildTaskIndexQuery::SCOPE_TENANT);
    }

    private function renderCollection(IndexTasksRequest $request, string $scope): Response
    {
        /** @var User $user */
        $user = $request->user();

        $tasks = BuildTaskIndexQuery::execute($request, $scope, $user, $this->authorizer)
            ->paginate($request->getPerPage());

        // `?item=` opens the preview on a task that may sit past the first page; it resolves
        // through the same scope, so no id outside it can be reached.
        $itemId = $request->validated('item');
        $item = $itemId
            ? BuildTaskIndexQuery::base($scope, $user, $this->authorizer)->with(['taskable', 'users:id,name,email,profile_photo_path'])->find($itemId)
            : null;

        return $this->inertiaResponse('Admin/Tasks/IndexTask', [
            'scope' => $scope,
            'data' => $tasks->getCollection()->map(fn (Task $task) => TaskResource::forListing($task, $user))->values(),
            'meta' => [
                'total' => $tasks->total(),
                'per_page' => $tasks->perPage(),
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
            ],
            'linkedTask' => $item ? TaskResource::forListing($item, $user) : null,
            'taskCounts' => fn () => BuildTaskIndexQuery::counts($scope, $user, $this->authorizer),
            'canViewAllTasks' => $user->can('viewAny', Task::class),
            'tenants' => $scope === BuildTaskIndexQuery::SCOPE_TENANT
                ? Tenant::query()
                    ->whereIn('id', BuildTaskIndexQuery::readableTenantIds($user, $this->authorizer))
                    ->orderBy('shortname')
                    ->get(['id', 'shortname'])
                    ->map(fn (Tenant $tenant) => ['id' => $tenant->id, 'shortname' => __($tenant->shortname)])
                : [],
        ]);
    }
}
