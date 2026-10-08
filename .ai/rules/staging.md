---
paths:
  - 'resources/js/Components/Staging*.vue'
  - 'resources/js/Composables/useStaging.ts'
  - 'app/Http/Middleware/Staging*.php'
  - 'app/Services/NotificationRouter.php'
  - 'app/Listeners/BlockExternalNotificationsOnStaging.php'
  - 'app/Support/StagingProtection.php'
  - 'app/Services/StagingMediaStore.php'
---

# Staging

## Staging differences: brief banner, details dialog, notes at the action
The staging banner stays one line (title · summary + "Kas kitaip?"); every difference is listed in its dialog, sourced from `useStaging().notes`.
A difference that changes what an action does (read-only files, SharePoint target, who gets an email) is shown with `<StagingNote topic="…">` next to that action — Files/Index, FileableFilesPanel, ActivityRequestReviewScreen — never added to the banner text.
New difference = a topic in `useStaging` + `staging.topics.*` in lang/admin/{lt,en}/staging.php + a StagingNote where it bites.

Staging mail (opt-in per notification, only to whoever caused it) is in audiences.md.

## Staging writes media only to its own disk
Staging's `public/uploads` links to production's storage. So on staging the `spatieMediaLibrary` disk points there read-only (flysystem read-only adapter), and `media-library.disk_name` is `stagingMedia` (`public/staging-media`), both derived from APP_ENV in config, with no env vars. `staging:refresh-database` empties `stagingMedia` after each restore, because restored media ids would otherwise land on yesterday's files; `StagingMediaStore` refuses a root that resolves into `public/uploads`.
- Guard media writes with `StagingProtection::ensureDiskIsWritable($disk)`, not `ensureFilesAreWritable()`; the file manager and shared folders stay read-only on staging.
- `StagingIsolationService::mediaErrors()` fails `staging:verify-isolation` if either disk setting drifts.
