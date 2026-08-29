---
paths:
  - 'app/Models/Task.php,app/Models/Traits/HasTasks.php,app/Http/Controllers/Admin/TaskController.php'
---

# Admin

## Deleting a task requires detaching its assignees first
`task_user.task_id` is a RESTRICT foreign key, so `$task->delete()` throws for any task with an assignee — which was every real task. Task::booted() now detaches in a `deleting` hook; never bypass it with a mass delete (`$model->tasks()->delete()` fires no model events). HasTasks cascades on the owner's `deleting`, soft delete included, via `$model->tasks->each->delete()`; restoring the owner does not bring tasks back, `tasks:repopulate` recreates the automatic ones. TaskController::destroy blocks deleting an auto-completing task except for super admins, who need the escape hatch for tasks that can no longer be completed. Task.php is exempted in DutiableDetachConventionTest — its `users()` is on task_user, not the dutiables pivot.
