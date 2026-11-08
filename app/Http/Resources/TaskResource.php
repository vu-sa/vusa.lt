<?php

namespace App\Http\Resources;

use App\Models\Task;
use App\Models\User;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /** @return array{id: string, name: string, description: string|null, due_date: string|null, completed_at: string|null, cancelled_at: string|null, created_at: string, updated_at: string, action_type: ActionType|null, metadata: array<string, mixed>|null, progress: array{current: int, total: int, percentage: int}|null, is_overdue: bool, can_be_manually_completed: bool, can_update: bool, icon: string, color: string, taskable: array{id: int|string, name: string|null, type: string}|null, taskable_type: string, taskable_id: string, users: list<array{id: string, name: string, profile_photo_path: string|null}>, can_delete: bool} */
    public static function forListing(Task $task, User $user): array
    {
        return [
            ...new self($task)->resolve(),
            'can_delete' => $task->isDeletableBy($user),
        ];
    }

    /** @return array{id: string, name: string, description: string|null, due_date: string|null, completed_at: string|null, cancelled_at: string|null, created_at: string, updated_at: string, action_type: ActionType|null, metadata: array<string, mixed>|null, progress: array{current: int, total: int, percentage: int}|null, is_overdue: bool, can_be_manually_completed: bool, can_update: bool, icon: string, color: string, taskable: array{id: int|string, name: string|null, type: string}|null, taskable_type: string, taskable_id: string, users: list<array{id: string, name: string, profile_photo_path: string|null}>} */
    #[\Override]
    public function toArray(Request $request): array
    {
        /** @var Model|null $taskable */
        $taskable = $this->taskable;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'due_date' => $this->due_date?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'action_type' => $this->action_type,
            'metadata' => $this->metadata,
            'progress' => $this->getProgress(),
            'is_overdue' => $this->isOverdue(),
            'can_be_manually_completed' => $this->canBeManuallyCompleted(),
            // A reader of the padalinys list is not necessarily allowed to tick it off.
            'can_update' => (bool) $request->user()?->can('update', $this->resource),
            'icon' => $this->icon,
            'color' => $this->color,
            'taskable' => $taskable ? [
                'id' => $taskable->getKey(),
                'name' => $taskable->getAttribute('title') ?? $taskable->getAttribute('name') ?? null,
                'type' => $this->taskable_type,
            ] : null,
            'taskable_type' => $this->taskable_type ?? '',
            'taskable_id' => $this->taskable_id,
            'users' => $this->users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'profile_photo_path' => $user->profile_photo_path,
            ])->all(),
        ];
    }
}
