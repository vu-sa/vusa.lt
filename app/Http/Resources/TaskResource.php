<?php

namespace App\Http\Resources;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
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
            'created_at' => $this->created_at->toISOString(),
            'action_type' => $this->action_type?->value,
            'metadata' => $this->metadata,
            'progress' => $this->getProgress(),
            'is_overdue' => $this->isOverdue(),
            'can_be_manually_completed' => $this->canBeManuallyCompleted(),
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
