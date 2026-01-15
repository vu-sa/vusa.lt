# Task System

This directory contains the task management system for the application. Tasks are workflow items that can be assigned to users and either manually completed or auto-completed by system events.

## Directory Structure

```
app/Tasks/
├── DTOs/
│   └── CreateTaskData.php      # Type-safe data transfer object for task creation
├── Enums/
│   └── ActionType.php          # Enum defining task types (Manual, Approval, Pickup, Return)
├── Handlers/
│   ├── TaskHandler.php         # Interface for task handlers
│   ├── BaseTaskHandler.php     # Base class with common functionality
│   ├── ManualTaskHandler.php   # Handles manually completable tasks
│   ├── ApprovalTaskHandler.php # Handles approval workflow tasks
│   ├── PickupTaskHandler.php   # Handles pickup tasks with progress tracking
│   └── ReturnTaskHandler.php   # Handles return tasks with progress tracking
└── Subscribers/
    ├── ApprovalTaskSubscriber.php      # Listens for approval events
    └── ReservationTaskSubscriber.php   # Listens for reservation state changes
```

## Task Types (ActionType)

| Type | Description | Completion |
|------|-------------|------------|
| `Manual` | Standard user tasks | User marks complete |
| `Approval` | Approval workflow tasks | Auto-completes on approval decision |
| `Pickup` | Resource pickup tracking | Auto-completes when all resources picked up |
| `Return` | Resource return tracking | Auto-completes when all resources returned |

## Usage

### Creating a Manual Task

```php
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Handlers\ManualTaskHandler;

$handler = app(ManualTaskHandler::class);
$data = CreateTaskData::manual(
    name: 'Review document',
    taskable: $document,
    users: $assignedUsers,
    dueDate: now()->addDays(7)->toDateString(),
);
$task = $handler->create($data);
```

### Creating an Approval Task

```php
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Handlers\ApprovalTaskHandler;

$handler = app(ApprovalTaskHandler::class);
$data = CreateTaskData::approval(
    name: 'Approve reservation request',
    taskable: $reservation,
    users: $approvers,
    dueDate: now()->addDays(3)->toDateString(),
);
$task = $handler->create($data);
```

### Progress-Tracked Tasks (Pickup/Return)

These tasks track multiple items and auto-complete when all items are processed:

```php
use App\Tasks\Handlers\PickupTaskHandler;

$handler = app(PickupTaskHandler::class);
$task = $handler->findOrCreate(
    name: 'Pick up reserved resources',
    model: $reservation,
    users: $users,
    dueDate: $startTime,
);

// Later, when an item is picked up:
$handler->incrementProgressForModel($reservation, 'Projector');
```

## Event Subscribers

### ReservationTaskSubscriber

Listens for `StateChanged` events on ReservationResource and:
- Creates Pickup tasks when resources become Reserved
- Creates Return tasks when resources become Lent
- Increments progress when resources transition states

### ApprovalTaskSubscriber

Listens for approval events and:
- Creates Approval tasks when `ApprovalRequested` is fired
- Completes Approval tasks when `ApprovalDecisionMade` is fired

## Adding a New Task Type

1. Add a new case to `ActionType` enum
2. Create a new handler class extending `BaseTaskHandler`
3. If event-driven, create or update a subscriber in `Subscribers/`
4. Register the subscriber in `EventServiceProvider`

## Legacy Support

The `TaskService` class maintains backward compatibility with static methods for existing code. New code should use the handler classes directly for better type safety and testability.
