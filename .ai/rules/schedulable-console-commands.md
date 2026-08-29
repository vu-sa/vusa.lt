---
paths:
  - 'app/Actions/ResolveTaskAudience.php,app/Listeners/HandleTaskCreated.php,app/Actions/Schedulable/TaskNotifier.php,app/Console/Commands/SendTaskOverdueReminders.php'
---

# Schedulable Console Commands

## Notify a task's audience, not its assignee list
Never send a task notification to $task->users — use $task->notifiableUsers() (App\Actions\ResolveTaskAudience). Assignment is snapshotted at the meeting's date by ResolveTaskAssignees, so backfilling an old sitting files the task on that term's roster; mailing it is wrong in both directions. The audience is the assignees who belong to the body (duty holder or nominated administrator) BOTH on the task's date and today. Deliberately unfiltered: manual tasks (a person picked those assignees) and tasks with no institution behind them (reservations — the person responsible stays responsible after their term).
