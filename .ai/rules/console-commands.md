---
paths:
  - 'app/Actions/**,app/Models/InstitutionAdministrator.php,app/Services/CommentableMentionResolver.php,app/Console/Commands/SendMeetingReminders.php'
---

# Console Commands

## Notification audiences are date-scoped; administrators replace members for tasks
`Institution::users()`, `Meeting::users()` and `$duty->users` are all-time HasManyDeep/MorphToMany relations. Never build a notification audience from them — a body with turnover notifies everyone who ever held a seat. `SendMeetingReminders` and `CommentableMentionResolver` both shipped that bug.

Three actions now own the question, and they answer different things:
- `GetInstitutionMembers` — every duty holder on a date (audience: reminders, comment/mention pools). Not type-filtered: the chair must still be reminded of their own sitting.
- `GetInstitutionRepresentatives` / `MeetingRepresentativeResolver` — only `studentu-atstovai`-typed duties, on a date (display, and the task fallback).
- `ResolveTaskAssignees` — the single decision point for *task* assignment: nominated administrators for the term, else date-scoped representatives. A replacement, not a union; that is what keeps a 46-seat body's sitting out of 46 inboxes. Audiences merge administrators in instead of narrowing.

`institution_administrators` is `(institution_id, cadence_id, user_id)`. An administrator is deliberately NOT a member: it must never feed `Institution::users()`, `duties.current_users`, `toSearchableArray()['current_user_names']` or the public contacts. It does widen `InstitutionAccessService::getAccessibleInstitutionIds()` and the dashboard, flagged `is_administered`.

Write rows through the `InstitutionAdministrator` model, never `administrators()->sync()/attach()/detach()` — BelongsToMany writes go through the raw query builder, so no model events fire and the access-cache invalidation in `booted()` is skipped. Same trap as the dutiables pivot (see system.md).
