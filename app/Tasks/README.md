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

## Testing

Tests follow the source structure for discoverability:

| Source | Test |
|--------|------|
| `app/Tasks/Handlers/PeriodicityGapTaskHandler.php` | `tests/Feature/Tasks/Handlers/PeriodicityGapTaskHandlerTest.php` |
| `app/Tasks/Subscribers/MeetingTaskSubscriber.php` | `tests/Feature/Tasks/Subscribers/MeetingTaskSubscriberTest.php` |
| `app/Tasks/Subscribers/ApprovalTaskSubscriber.php` | `tests/Feature/Tasks/Subscribers/ApprovalTaskSubscriberTest.php` |
| `app/Tasks/Subscribers/ReservationTaskSubscriber.php` | `tests/Feature/Tasks/Subscribers/ReservationTaskSubscriberTest.php` |
| `app/Tasks/Subscribers/InstitutionCheckInTaskSubscriber.php` | `tests/Feature/Tasks/Subscribers/InstitutionCheckInTaskSubscriberTest.php` |

### Running Task Tests

```bash
# Run all task tests
vendor/bin/sail artisan test --compact tests/Feature/Tasks/

# Run only subscriber tests
vendor/bin/sail artisan test --compact tests/Feature/Tasks/Subscribers/

# Run only handler tests
vendor/bin/sail artisan test --compact tests/Feature/Tasks/Handlers/
```

### Architecture Tests

Task system conventions are enforced in `tests/Architecture/TasksArchitectureTest.php`:
- Handlers must implement `TaskHandler` interface and extend `BaseTaskHandler`
- Handlers must have `Handler` suffix
- Subscribers must have `Subscriber` suffix
- DTOs must be `final` and `readonly`
- Enums must be PHP enums

Run architecture tests:
```bash
vendor/bin/sail artisan test --compact tests/Architecture/TasksArchitectureTest.php
```

## Adding a New Task Type

1. Add a new case to `ActionType` enum
2. Create a new handler class extending `BaseTaskHandler`
3. If event-driven, create or update a subscriber in `Subscribers/`
4. Register the subscriber in `EventServiceProvider`
5. **Add a test file** in the corresponding `tests/Feature/Tasks/` subdirectory
6. Run architecture tests to verify conventions

## Legacy Support

The `TaskService` class maintains backward compatibility with static methods for existing code. New code should use the handler classes directly for better type safety and testability.
