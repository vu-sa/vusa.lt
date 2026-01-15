<?php

namespace App\Services;

use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\ManualTaskHandler;
use App\Tasks\Handlers\PickupTaskHandler;
use App\Tasks\Handlers\ReturnTaskHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Service for creating and managing tasks.
 *
 * This service acts as a facade for the task handler classes, providing
 * a simple API for creating tasks throughout the application.
 *
 * For specific task types (Approval, Pickup, Return), prefer using the
 * dedicated handlers in App\Tasks\Handlers directly, as they provide
 * additional functionality specific to each task type.
 */
class TaskService
{
    public function __construct(
        protected ManualTaskHandler $manualHandler,
        protected PickupTaskHandler $pickupHandler,
        protected ReturnTaskHandler $returnHandler,
    ) {}

    /**
     * Create a task using the provided data.
     *
     * @deprecated Use specific handlers or CreateTaskData for type-safe task creation
     *
     * @param  Model&object{id: int|string}  $model
     * @param  ActionType|string|null  $actionType  Optional action type for auto-completion
     * @param  array|null  $metadata  Optional metadata for progress tracking
     */
    public static function storeTask(
        string $name,
        Model $model,
        Collection $users,
        ?string $due_date = null,
        ActionType|string|null $actionType = null,
        ?array $metadata = null
    ): Task {
        $data = new CreateTaskData(
            name: $name,
            taskable: $model,
            users: $users,
            dueDate: $due_date,
            actionType: $actionType instanceof ActionType ? $actionType : null,
            metadata: $metadata,
        );

        $handler = new ManualTaskHandler;

        return $handler->create($data);
    }

    /**
     * Find or create a task with progress tracking for a reservation.
     * If task exists, updates the total items count.
     *
     * @deprecated Use PickupTaskHandler or ReturnTaskHandler directly
     *
     * @param  Model&object{id: int|string}  $model
     */
    public static function findOrCreateProgressTask(
        string $name,
        Model $model,
        Collection $users,
        ?string $due_date,
        ActionType $actionType,
        int $totalItems = 1
    ): Task {
        $handler = match ($actionType) {
            ActionType::Pickup => new PickupTaskHandler,
            ActionType::Return => new ReturnTaskHandler,
            default => throw new \InvalidArgumentException('Progress tasks only support Pickup and Return action types'),
        };

        return $handler->findOrCreate(
            name: $name,
            model: $model,
            users: $users,
            dueDate: $due_date,
        );
    }

    /**
     * Create a manual task (non-static method for DI usage).
     */
    public function createManualTask(CreateTaskData $data): Task
    {
        return $this->manualHandler->create($data);
    }

    /**
     * Create or find a pickup task with progress tracking.
     */
    public function createPickupTask(
        string $name,
        Model $model,
        Collection $users,
        ?string $dueDate = null
    ): Task {
        return $this->pickupHandler->findOrCreate($name, $model, $users, $dueDate);
    }

    /**
     * Create or find a return task with progress tracking.
     */
    public function createReturnTask(
        string $name,
        Model $model,
        Collection $users,
        ?string $dueDate = null
    ): Task {
        return $this->returnHandler->findOrCreate($name, $model, $users, $dueDate);
    }
}
