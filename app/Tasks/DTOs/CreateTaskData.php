<?php

namespace App\Tasks\DTOs;

use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Data Transfer Object for task creation.
 *
 * Provides type-safe, validated data for creating tasks throughout the application.
 */
readonly class CreateTaskData
{
    /**
     * @param  string  $name  The task name/title
     * @param  Model  $taskable  The model this task is attached to
     * @param  Collection<int, \App\Models\User>  $users  Users assigned to the task
     * @param  string|null  $dueDate  Due date for the task
     * @param  ActionType|null  $actionType  Type of action (Manual, Approval, Pickup, Return)
     * @param  array<string, mixed>|null  $metadata  Additional metadata (e.g., progress tracking)
     * @param  string|null  $description  Instructions or description for the task
     */
    public function __construct(
        public string $name,
        public Model $taskable,
        public Collection $users,
        public ?string $dueDate = null,
        public ?ActionType $actionType = null,
        public ?array $metadata = null,
        public ?string $description = null,
    ) {}

    /**
     * Create a new instance with progress tracking metadata.
     */
    public static function withProgress(
        string $name,
        Model $taskable,
        Collection $users,
        ?string $dueDate,
        ActionType $actionType,
        int $totalItems = 1
    ): self {
        return new self(
            name: $name,
            taskable: $taskable,
            users: $users,
            dueDate: $dueDate,
            actionType: $actionType,
            metadata: [
                'items_total' => $totalItems,
                'items_completed' => 0,
            ],
        );
    }

    /**
     * Create a manual task data object.
     */
    public static function manual(
        string $name,
        Model $taskable,
        Collection $users,
        ?string $dueDate = null
    ): self {
        return new self(
            name: $name,
            taskable: $taskable,
            users: $users,
            dueDate: $dueDate,
            actionType: ActionType::Manual,
        );
    }

    /**
     * Create an approval task data object.
     */
    public static function approval(
        string $name,
        Model $taskable,
        Collection $users,
        ?string $dueDate = null
    ): self {
        return new self(
            name: $name,
            taskable: $taskable,
            users: $users,
            dueDate: $dueDate,
            actionType: ActionType::Approval,
        );
    }

    /**
     * Check if this task has progress tracking.
     */
    public function hasProgressTracking(): bool
    {
        return $this->metadata !== null
            && isset($this->metadata['items_total']);
    }
}
