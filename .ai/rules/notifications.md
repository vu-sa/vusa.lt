---
paths:
  - 'app/Notifications/**'
---

# Notifications

## Notifications: one content contract, urgency picks the default channel
- A notification declares `title()`, `body()`, `primaryAction()` (+ `secondaryAction()` only for a binary answer, e.g. "Taip, fiksuoti" / "Ne, nevyko") and `context()` (2–4 label/value rows) once. The in-app list, the branded email and the push all render those same fields; copy is never written twice. `actions()` is a deprecated derived accessor.
- Subjects are ≤60 chars and front-load the ask and the object, with no "VU SA sistema:" prefix. Locale follows the recipient.
- `NotificationUrgency` sets each type's default: act → email now (+ push where set), know → digest, record/onboarding → bell only. The user then chooses per type (email now / digest / none, push on/off); in-app is never gated, not even by mute. Quiet hours 22:00–07:00 delay push and digests.
- Emails say why they arrived, link to Pranešimų nustatymai, are signed by the institution koordinatorius where one exists, and have a plain-text part. Email is light-only, one column, square, one brand button.
- `NotificationCategory::color()` maps onto `--cat-*`, never onto status tokens.

## Adding a notification: a catalogued type, an audience, one notice per event
- Every notification extends BaseNotification and returns a `NotificationType` case from `type()`; category, urgency, default email (Immediate/Digest/Off) and default push come from that case, never from overrides of `via()`. A new kind of message = a new case, not a flag on an existing class.
- For a new configurable case: set `section()`, `urgency()`, `defaultPush()` (and `lockedEmail()` only for role-inbox mail), add `notifications.types.<value>.label` + `.description` ("when you get it") in lt and en, and a branch in `NotificationAudience::canReceive()` naming who can get it — the settings page lists only those. Record-only messages default to email Off (bell only).
- Build recipients with the date-scoped resolvers (ResolveTaskAudience, ResolveMeetingNotificationAudience, GetResourceManagers…), never all-time relations, and exclude the actor whose action caused it.
- One event → one notice per person: if a task, approval request or status change already reaches someone for the same event, do not send them a second notification; say so in the `how_*` copy on Pranešimų nustatymai.
