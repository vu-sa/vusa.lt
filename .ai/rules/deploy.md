---
paths:
  - '.github/workflows/deploy*.yml'
  - 'app/Console/Commands/Deployment*.php'
  - 'app/Console/Commands/StagingRefreshDatabase.php'
  - 'deployment/**'
---

# Deploy

## Deploy pipeline: what may and may not run inside the maintenance window
The outage runs from the `scp deployment/maintenance.php` in deploy-common.yml to `deployment:run`'s `online` step; keep it at ~15s:

- Slow, side-effect-free work belongs in the **Pre-flight (site still online)** step: the vendor extract, `rm -rf vendor.old`, `git fetch`, and the database backup. `deployment:backup` uses `--single-transaction`, so it is consistent and non-blocking against a live site. Moving any of it back inside the window silently restores minutes of downtime.
- Non-critical steps must sit **after** `online` (`search`, `reverb`). `search:reindex` drops and recreates all 14 Typesense collections — 53-63s, paid even for a CSS-only change. `DeployWorkflowTest` enforces both orderings.
- `queue:restart`/`reverb:restart` must come **after** `optimize`: `optimize:clear` runs `cache:clear`, which would wipe the restart signal. Without them, workers run stale code until `--max-time=3600` recycles them and Reverb never restarts at all.
- **Never enable `clean-untracked` for production.** Its repo root holds ~1 GB of untracked directories (two copies of the old LimeSurvey install) that `git clean -fd` would delete silently.
- `DeploymentResume` derives step order from `DeploymentRun::STEPS`. Do not restate the order anywhere.
- `staging:refresh-database` drops every table in the database it points at. Its `APP_ENV=staging` guard is deliberately not overridable — no `--force`, no prompt.

## Heavy data backfills are queued from the migration, never run in it
Migrations run inside the ~15 s maintenance window, and staging re-runs them every night against restored production data. A migration that moves files or rebuilds images only plucks ids and dispatches a chain on the `long-running` connection (precedent: `dispatch_legacy_image_backfill` → `LegacyImageBackfill`). The jobs are idempotent, change no owner timestamps, end with search upserts (never `search:reindex`), and expose a `--status` command that the follow-up release's guard checks. On staging, cap the work to a sample derived from APP_ENV.
