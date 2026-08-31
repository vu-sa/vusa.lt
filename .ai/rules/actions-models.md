---
paths:
  - 'app/Tasks/**,app/Actions/ResyncTaskAssigneesForCadence.php,app/Models/Cadence.php'
---

# Actions Models

## Task assignment is stored, so every path that revives or re-scopes a task must re-resolve it
`task_user` is a snapshot, not a derived list — `ResolveTaskAudience` only drops assignees who have since left the body, so it cannot rescue a roster that predates a nomination. Any code that hands a task back to live work has to call `ResolveTaskAssignees` again.

Two paths that did not, and now do:
- `AgendaCompletionTaskHandler::reopenIfNeeded()` clears `completed_at`. `ResyncTaskAssigneesForCadence` deliberately skips completed tasks, so a task completed before its term was staffed came back with its old roster and mailed all of it on the next auto-completion.
- `Cadence::booted()` re-staffs on a date change or delete: moving a term moves meetings in and out of it. The window swept is the union of the old and new dates — a meeting that fell *out* needs handing back to the membership. Scoped to `ResyncTaskAssigneesForCadence::institutionsStaffedOn()`, since nowhere else can the answer change. It lives on the model, not `CadenceController`, because dates also move from the meeting side via `SyncCadenceDatesFromAnchors`.

Regression coverage: `tests/Feature/Tasks/InstitutionAdministratorMailScopeTest.php` (asserts real `notification_digest_queue` rows, not `Notification::fake()`).
