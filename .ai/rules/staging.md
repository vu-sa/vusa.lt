---
paths:
  - 'resources/js/Components/Staging*.vue'
  - 'resources/js/Composables/useStaging.ts'
  - 'app/Http/Middleware/Staging*.php'
  - 'app/Services/NotificationRouter.php'
  - 'app/Listeners/BlockExternalNotificationsOnStaging.php'
---

# Staging

## Staging differences: brief banner, details dialog, notes at the action
The staging banner stays one line (title · summary + "Kas kitaip?"); every difference is listed in its dialog, sourced from `useStaging().notes`.
A difference that changes what an action does (read-only files, SharePoint target, who gets an email) is shown with `<StagingNote topic="…">` next to that action — Files/Index, FileableFilesPanel, ActivityRequestReviewScreen — never added to the banner text.
New difference = a topic in `useStaging` + `staging.topics.*` in lang/admin/{lt,en}/staging.php + a StagingNote where it bites.

Staging mail (opt-in per notification, only to whoever caused it) is in audiences.md.
