---
paths:
  - '.github/workflows/deploy*.yml,app/Console/Commands/Deployment*.php,app/Console/Commands/StagingRefreshDatabase.php,deployment/**'
---

# Commands Console Commands

## Deploy pipeline: what may and may not run inside the maintenance window
The outage runs from the `scp deployment/maintenance.php` in deploy-common.yml to `deployment:run`'s `online` step. It was 1m33s-4m18s; it is now ~15s. Keep it that way:

- Slow, side-effect-free work belongs in the **Pre-flight (site still online)** step: the vendor extract, `rm -rf vendor.old`, `git fetch`, and the database backup. `deployment:backup` uses `--single-transaction`, so it is consistent and non-blocking against a live site. Moving any of it back inside the window silently restores minutes of downtime.
- Non-critical steps must sit **after** `online` (`search`, `reverb`). `search:reindex` drops and recreates all 14 Typesense collections — 53-63s, paid even for a CSS-only change. `DeployWorkflowTest` enforces both orderings.
- `queue:restart`/`reverb:restart` must come **after** `optimize`: `optimize:clear` runs `cache:clear`, which would wipe the restart signal. Without them, workers run stale code until `--max-time=3600` recycles them and Reverb never restarts at all (it had 53 days of uptime across dozens of deploys).
- **Never enable `clean-untracked` for production.** Its repo root holds ~1 GB of untracked directories (two copies of the old LimeSurvey install) that `git clean -fd` would delete silently.
- `DeploymentResume` derives step order from `DeploymentRun::STEPS`. Do not restate the order anywhere; the hand-maintained copy had already drifted.
- `staging:refresh-database` drops every table in the database it points at. Its `APP_ENV=staging` guard is deliberately not overridable — no `--force`, no prompt.
