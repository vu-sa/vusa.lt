---
paths:
  - 'app/Tasks/**'
  - 'app/Models/Task.php'
  - 'app/Models/Traits/HasTasks.php'
  - 'app/Http/Controllers/Admin/TaskController.php'
  - 'app/Actions/ResolveTaskAssignees.php'
  - 'app/Actions/ResolveTaskAudience.php'
  - 'app/Actions/ResyncTaskAssigneesForCadence.php'
  - 'app/Actions/Schedulable/**'
  - 'app/Listeners/HandleTaskCreated.php'
  - 'app/Console/Commands/SendTaskOverdueReminders.php'
  - 'app/Models/Cadence.php'
---

# Tasks

## Task assignment is stored, so every path that revives or re-scopes a task must re-resolve it
`task_user` is a snapshot, not a derived list — `ResolveTaskAudience` only drops assignees who have since left the body, so it cannot rescue a roster that predates a nomination. Any code that hands a task back to live work has to call `ResolveTaskAssignees` again.

Two paths that did not, and now do:
- `AgendaCompletionTaskHandler::reopenIfNeeded()` clears `completed_at`. `ResyncTaskAssigneesForCadence` deliberately skips completed tasks, so a task completed before its term was staffed came back with its old roster and mailed all of it on the next auto-completion.
- `Cadence::booted()` re-staffs on a date change or delete: moving a term moves meetings in and out of it. The window swept is the union of the old and new dates — a meeting that fell *out* needs handing back to the membership. Scoped to `ResyncTaskAssigneesForCadence::institutionsStaffedOn()`, since nowhere else can the answer change. It lives on the model, not `CadenceController`, because dates also move from the meeting side via `SyncCadenceDatesFromAnchors`.

Regression coverage: `tests/Feature/Tasks/InstitutionAdministratorMailScopeTest.php` (asserts real `notification_digest_queue` rows, not `Notification::fake()`).

## Deleting a task requires detaching its assignees first
`task_user.task_id` is a RESTRICT foreign key, so `$task->delete()` throws for any task with an assignee — which was every real task. Task::booted() now detaches in a `deleting` hook; never bypass it with a mass delete (`$model->tasks()->delete()` fires no model events). HasTasks cascades on the owner's `deleting`, soft delete included, via `$model->tasks->each->delete()`; restoring the owner does not bring tasks back, `tasks:repopulate` recreates the automatic ones. TaskController::destroy blocks deleting an auto-completing task except for super admins, who need the escape hatch for tasks that can no longer be completed. Task.php is exempted in DutiableDetachConventionTest — its `users()` is on task_user, not the dutiables pivot.

## Notify a task's audience, not its assignee list
Never send a task notification to $task->users — use $task->notifiableUsers() (App\Actions\ResolveTaskAudience). Assignment is snapshotted at the meeting's date by ResolveTaskAssignees, so backfilling an old sitting files the task on that term's roster; mailing it is wrong in both directions. The audience is the assignees who belong to the body (duty holder or nominated administrator) BOTH on the task's date and today. Deliberately unfiltered: manual tasks (a person picked those assignees) and tasks with no institution behind them (reservations — the person responsible stays responsible after their term).
