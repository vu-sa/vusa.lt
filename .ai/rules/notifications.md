---
paths:
  - 'app/Notifications/**'
---

# Notifications

## Notifications: one content contract, urgency picks the channel
- A notification declares `title()`, `body()`, `primaryAction()` (+ `secondaryAction()` only for a binary answer, e.g. "Taip, fiksuoti" / "Ne, nevyko") and `context()` (2–4 label/value rows) once. The in-app list, the branded email and the push all render those same fields; copy is never written twice. `actions()` is a deprecated derived accessor.
- Subjects are ≤60 chars and front-load the ask and the object, with no "VU SA sistema:" prefix. Locale follows the recipient.
- `NotificationUrgency` is the channel policy: act → email now (+ push where set), know/record → digest + in-app. No action and no deadline → never email. Quiet hours 22:00–07:00 delay push and digests.
- Emails say why they arrived, link to Pranešimų nustatymai, are signed by the institution koordinatorius where one exists, and have a plain-text part. Email is light-only, one column, square, one brand button.
- `NotificationCategory::color()` maps onto `--cat-*`, never onto status tokens.
