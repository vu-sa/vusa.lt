<?php

namespace App\Tasks\DTOs;

use App\Models\User;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

readonly class CreateTaskData
{
    /**
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>|null  $metadata
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

    public static function withProgress(
        string $name,
        Model $taskable,
        Collection $users,
        ?string $dueDate,
        ActionType $actionType,
        int $totalItems = 1,
        ?string $description = null
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
            description: $description,
        );
    }

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

    public function hasProgressTracking(): bool
    {
        return $this->metadata !== null
            && isset($this->metadata['items_total']);
    }
}
