---
paths:
  - 'database/migrations/**'
  - 'tests/Feature/Migrations/**'
---

# Migrations

## Two-release changes: mark the scaffolding, remove it behind a guard
When a change needs a transition (backfill + read fallback, old and new keys side by side, a legacy upload mode), ship it in two releases. Mark every transitional piece, in app code, frontend and migrations alike, with a one-line `Transitional(<slug>): remove when <condition>` comment, so `grep -rn 'Transitional(<slug>)'` lists everything the second release deletes.
- The condition is a production state you can check (a `--status` command or a query), never a date.
- The removal release's migration re-checks that state and throws if it does not hold. Staging runs it against restored prod data first, so a failed guard surfaces there.
- A data-only migration that calls scaffolding classes breaks once they are deleted. In the removal release, delete that migration and its test instead of leaving it dangling. Prod and staging already record it in `migrations`, and a fresh database has no data for it to touch. Schema migrations are never deleted this way.

## Migration tests are temporary; prune them with their migration
A test in tests/Feature/Migrations proves a data migration on prod-shaped data before it deploys. Once the migration has run on production it has no further value. Delete the test together with the data-only migration it `require`s; never leave one without the other.
The migration set itself should be pruned over time. Squash schema migrations with `schema:dump --prune`, but only as a planned change: tests run every migration on SQLite `:memory:`, and `database/schema/mysql-schema.sql` is a stale March 2024 dump. A squash must regenerate the MySQL dump and give tests an SQLite schema in the same change. Until then, keep schema migrations.
