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

## Heavy data backfills are started by hand, never by a migration
A backfill that moves files or rebuilds images ships as an artisan command and is run manually after deploy: on staging first, where it can be inspected, then on production. Migrations stay schema-only and never dispatch it. The command must be predictable:
- With no options it does the whole job (queued on `long-running` as a batch with `allowFailures()`, ending in search upserts, never `search:reindex`). Anything smaller is an explicit flag (`--limit`, `--target`); never a cap derived from APP_ENV.
- It is idempotent and resumable, has `--status` (the check a later removal release guards on) and `--sync` for running in the foreground, and shows progress (`.ai/rules/commands.md`).
- Deleting what it replaced is a separate, explicit flag, never part of the default run.
- Staging's nightly refresh restores production's database, so its results are gone the next morning; re-run it to test again.
Precedent: `media:migrate-images`.
