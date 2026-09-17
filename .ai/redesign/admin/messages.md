# Messages — email, push and in-app

> **Admin redesign.** Read when: you are changing a notification, an email or a push payload.
> Index: [README.md](README.md) · Reps: [reps.md](reps.md)

## Messages — email, push and in-app

**In scope** (2026-09-17). Most rep sessions start in an email, so the message is part of the
interface, not a follow-up project.

### Today

- **24 notification classes** on `BaseNotification`, which already defines `title()`, `body()`,
  `url()`, `actions()`, `category()`, digest support and a WebPush channel. That is the content
  contract to build on.
- **The email look is stock Laravel** (`resources/views/vendor/mail/html/*`): white card, blue button,
  `color-scheme: light`, the sample logo block. Nothing of the brand.
- **Five bespoke blades** sit outside that layout: `comment-posted`, `assigned-to-resource`,
  `feedback`, `notification-digest`, the two registration pairs, plus `mail/reminder-to-login-notification`.
- `NotificationCategory::color()` carries its own hue list that collides with the status roles (Task
  orange, System red, Duty amber) — see [Colour system](rules/visual.md#colour-system).
- Digests exist (`ProcessNotificationDigests`, `PruneNotificationDigests`); the admin has a mail
  queue page; **Mailpit runs in Sail**, so every template is checkable locally.

### Rules

1. **One content contract, three renderings.** A notification declares *what happened*, *what you
   must do*, *where* and *about what* — once. The in-app list, the email and the push render the same
   fields; no copy is written twice. Extend `BaseNotification` with `primaryAction()` (label + url)
   and `context()` (2–4 label/value pairs: institution, date, unit, deadline).
2. **Subjects front-load the ask and the object**, no prefix noise: "Užpildyk vakarykščio Senato
   posėdžio darbotvarkę", not "VU SA sistema: pranešimas apie posėdį". ≤ 60 characters.
3. **One primary action per message**; two only when the answer is binary (R-a: *Taip, fiksuoti* /
   *Ne, nevyko*). Buttons are verbs.
4. **Context as rows, not prose** — institution, date, unit, deadline as label/value pairs.
5. **Every email says why it arrived** and links to Pranešimų nustatymai.
6. **Signed by a person** where one exists — the institution's koordinatorius (O22) — never by
   "VU SA sistema". Addressed to the sekretorius when it is an operational nudge.
7. **Plain-text alternative** for every email; read it once to check it makes sense.
8. **Locale follows the recipient.**
9. **Urgency decides the channel** (table below). Everything else goes to the digest.
10. **Deep links land somewhere self-sufficient** and survive login (U22): the page must let the job
    finish on a phone.
11. **Email is email:** tables, inline styles, one column, ~600px, no web fonts, light-only
    (`color-scheme: light` stays), brand as a red button plus hairline rules, the wordmark as text or
    a hosted PNG. Square corners, no shadows — the same language, expressed in what mail clients support.
12. **Push is one line and one action**, with the same wording as the email subject.
13. **Quiet hours:** nothing non-urgent between 22:00 and 07:00 — queue it to the morning.
14. **Never send what the app can show silently.** No action and no deadline → in-app only.

### Channel policy (first pass — confirm per type while doing the work)

| Notification | Category | Urgency | Email now | Push | Digest | In-app |
|---|---|---|---|---|---|---|
| `TaskAssigned` (due ≤ 7 d) | task | act | ✓ | ✓ | — | ✓ |
| `TaskOverdue`, `TaskReminder` | task | act | ✓ answerable | ✓ | — | ✓ |
| `MeetingReminder` | meeting | act | ✓ | ✓ | — | ✓ |
| `InstitutionActivity` (periodicity gap) | meeting | act | ✓ answerable (U21) | — | — | ✓ |
| `ApprovalRequested` | reservation | act | ✓ | ✓ | — | ✓ |
| `AssignedToResource` | reservation | act | ✓ | — | — | ✓ |
| `CommentPosted` — mention | comment | act | ✓ | ✓ | — | ✓ |
| `CommentPosted` — thread activity | comment | know | — | — | ✓ | ✓ |
| `DutyExpiring` | duty | act | ✓ | — | — | ✓ |
| `MemberRegistration`, `StudentRepRegistration` | registration | act (recipient) | ✓ | — | — | ✓ |
| `ReservationStatusChanged`, `ApprovalDecision` | reservation | know | — | — | ✓ | ✓ |
| `MeetingCreated`, `MeetingAgendaCompleted` | meeting | know | — | — | ✓ | ✓ |
| `NewsPublished` | news | know | — | — | ✓ | ✓ |
| `CalendarReminder` | calendar | know | — | ✓ opt-in | ✓ | ✓ |
| `TaskCompleted`, `TaskAutoCompleted` | task | record | — | — | ✓ | ✓ |
| `SupportRequestStatusChanged` | system | know | — | — | ✓ | ✓ |
| `Welcome` | system | onboarding | ✓ | — | — | ✓ |
| `ReminderToLogin` | system | re-engagement | ✓ | — | — | — |

### Work

- Brand the mail layout once (`vendor/mail/html/*`), then delete the bespoke blades that exist only
  because the layout was ugly.
- The digest follows the same structure: grouped by category, each item = title + context + link, one
  "Atidaryti sistemą" at the end.
- Remap `NotificationCategory::color()` onto `--cat-*`.
- Per-type pass with the table above as the checklist: subject, primary action, context, channel,
  signature.
- Screenshot every template from Mailpit at 360px and desktop; include them in the phase gate.
- Check the push payloads (title, body, action, deep link) against the same contract.
