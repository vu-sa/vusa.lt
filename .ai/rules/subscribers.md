---
paths:
  - 'app/Actions/Get*.php,app/Actions/Resolve*.php,app/Tasks/Subscribers/**'
---

# Subscribers

## One word per group of people — "administrator" means a nominated row, nothing else
Each action answers one checkable question; do not reuse a name for a union of them.

- `GetInstitutionAdministrators` — nominated for a term (`institution_administrators` rows). "Administrator" means this and only this, in code and in the UI.
- `GetInstitutionMembers` — holds any duty there on a date. `GetInstitutionRepresentatives` — holds a `studentu-atstovai` duty there on a date.
- `GetInstitutionFollowersToNotify` — opted in via `institution_follows`, minus the mutes.
- `GetMeetingOverseers` — the by-position audience: institution managers ∪ tenant-visibility roles ∪ global-visibility roles ∪ the nominated administrators. Was `GetMeetingAdministrators`, which read as if nominating someone were what put a coordinator on the list.
- `ResolveMeetingNotificationAudience` — overseers ∪ followers. The only one meeting notifications should call; both subscriber call sites used to hand-merge the two halves.
- `ResolveTaskAssignees` (who carries it) and `ResolveTaskAudience` (which of them still hears about it) stay distinct — assignment narrows to administrators, audiences union.
