<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildTaskIndexQuery;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexTasksRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\ModelAuthorizer;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskApiController extends ApiController
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    /**
     * The task collection's refreshes (search, filters, "Rodyti daugiau") for both scopes.
     */
    public function index(IndexTasksRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $scope = $request->validated('scope') ?? BuildTaskIndexQuery::SCOPE_MINE;

        if ($scope === BuildTaskIndexQuery::SCOPE_TENANT) {
            $this->authorizeApi('viewAny', Task::class);
        }

        $query = fn (IndexTasksRequest $request) => BuildTaskIndexQuery::execute($request, $scope, $user, $this->authorizer);
        $tasks = $query($request)->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $tasks->getCollection()->map(fn (Task $task) => TaskResource::forListing($task, $user))->values(),
            'total' => $tasks->total(),
            'per_page' => $tasks->perPage(),
            'current_page' => $tasks->currentPage(),
            'last_page' => $tasks->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['completion', 'taskable_type', 'tenant', 'overdue', 'auto', 'assigned'], $query),
        ]);
    }

    /**
     * Get tasks for the current user (used by TasksIndicator component).
     */
    public function indicator(Request $request): JsonResponse
    {
        $this->requireAuth($request);

        $limit = $request->input('limit', 5);

        $tasks = Task::with('taskable')
            ->whereHas('users', function ($query): void {
                $query->where('users.id', Auth::id());
            })
            ->whereNull('completed_at')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (Task $task) => $this->transformTaskForIndicator($task));

        return $this->jsonSuccess($tasks);
    }

    /**
     * Transform task for indicator display with computed properties.
     */
    protected function transformTaskForIndicator(Task $task): array
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'due_date' => $task->due_date?->toDateString(),
            'taskable_type' => $task->taskable_type,
            'taskable_id' => $task->taskable_id,
            'completed_at' => $task->completed_at?->toISOString(),
            'action_type' => $task->action_type?->value,
            'metadata' => $task->metadata,
            'progress' => $task->getProgress(),
            'is_overdue' => $task->isOverdue(),
            'can_be_manually_completed' => $task->canBeManuallyCompleted(),
            'icon' => $task->icon,
            'color' => $task->color,
            'taskable' => $task->taskable ? [
                'id' => $task->taskable->getKey(),
                'name' => $task->taskable->name ?? $task->taskable->title ?? null,
            ] : null,
        ];
    }
}
