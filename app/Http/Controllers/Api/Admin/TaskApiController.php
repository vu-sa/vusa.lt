<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskApiController extends ApiController
{
    /**
     * Get tasks for the current user (used by TasksIndicator component).
     */
    public function indicator(Request $request): JsonResponse
    {
        $this->requireAuth($request);

        $limit = $request->input('limit', 5);

        $tasks = Task::with('taskable')
            ->whereHas('users', function ($query) {
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
            /** @phpstan-ignore ternary.alwaysTrue (taskable may be null if parent was deleted) */
            'taskable' => $task->taskable ? [
                'id' => $task->taskable->getKey(),
                'name' => $task->taskable->name ?? $task->taskable->title ?? null,
            ] : null,
        ];
    }
}
