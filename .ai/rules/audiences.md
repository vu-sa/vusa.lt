---
paths:
  - 'app/Actions/Get*.php'
  - 'app/Actions/Resolve*.php'
  - 'app/Tasks/Subscribers/**'
  - 'app/Models/InstitutionAdministrator.php'
  - 'app/Services/CommentableMentionResolver.php'
  - 'app/Console/Commands/SendMeetingReminders.php'
  - 'app/Notifications/**'
---

# Audiences: who is in a group of people

## Notification audiences are date-scoped
`Institution::users()`, `Meeting::users()` and `$duty->users` are all-time HasManyDeep/MorphToMany relations. Never build a notification audience from them — a body with turnover notifies everyone who ever held a seat. `SendMeetingReminders` and `CommentableMentionResolver` both shipped that bug. Use the actions below.

## One word per group of people — "administrator" means a nominated row, nothing else
Each action answers one checkable question; do not reuse a name for a union of them.

- `GetInstitutionAdministrators` — nominated for a term (`institution_administrators` rows). "Administrator" means this and only this, in code and in the UI.
- `GetInstitutionMembers` — every duty holder there on a date (reminders, comment/mention pools). Not type-filtered: the chair must still be reminded of their own sitting.
- `GetInstitutionRepresentatives` / `MeetingRepresentativeResolver` — only `studentu-atstovai`-typed duties, on a date (display, and the task fallback).
- `GetInstitutionFollowersToNotify` — opted in via `institution_follows`, filtered by meeting visibility.
- `GetMeetingOverseers` — the by-position audience: institution managers ∪ tenant-visibility roles ∪ global-visibility roles ∪ the nominated administrators.
- `ResolveMeetingNotificationAudience` — overseers ∪ followers. The only one meeting notifications should call.
- `ResolveTaskAssignees` — the single decision point for *task* assignment: nominated administrators for the term, else date-scoped representatives. A replacement, not a union; that is what keeps a 46-seat body's sitting out of 46 inboxes. `ResolveTaskAudience` (which assignees still hear about it) is distinct — see tasks.md.

## Administrators are not members
`institution_administrators` is `(institution_id, cadence_id, user_id)`. An administrator must never feed `Institution::users()`, `duties.current_users`, `toSearchableArray()['current_user_names']` or the public contacts. It does widen `InstitutionAccessService::getAccessibleInstitutionIds()` and the dashboard, flagged `is_administered`.

Write rows through the `InstitutionAdministrator` model, never `administrators()->sync()/attach()/detach()` — BelongsToMany writes go through the raw query builder, so no model events fire and the access-cache invalidation in `booted()` is skipped. Same trap as the dutiables pivot (see app.md).

## Staging mail is opt-in per notification and goes only to whoever caused it
Staging's default mailer stays `log` (StagingIsolationService enforces it). A notification may mail on staging only by implementing `Contracts\SendsMailOnStaging`; NotificationRouter then routes it to `stagingMailRecipient()`'s users.email (never duty inboxes, which the scrub leaves real), BlockExternalNotificationsOnStaging drops it when that is null or @staging.invalid, and its toMail() names `->mailer('smtp')` on staging only.
Return null for automatic sends (tasks, scheduler) so they never mail. Opting in also needs a `StagingNote topic="mail"` at the send point.
