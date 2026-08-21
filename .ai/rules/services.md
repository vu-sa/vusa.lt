---
paths:
  - 'app/Services/**'
---

# Services

## resetCache() no longer flushes the global permission cache by default
`ModelAuthorizer::resetCache($user, bool $flushGlobal = false)` only clears that user's `auth:duties:{id}` cache by default. It used to also call `PermissionRegistrar::forgetCachedPermissions()` unconditionally, which flushed Spatie's shared `spatie.permission.cache` for the whole app on every call — including from `UpdateLastAction` middleware, meaning one user's page view invalidated permission caching app-wide on every authenticated request.

Spatie's own `HasRoles`/`HasPermissions` trait methods (`assignRole`, `givePermissionTo`, `syncPermissions`, ...) and the `Role`/`Permission` models' `RefreshesPermissionCache` hooks already self-flush that shared cache. Only pass `flushGlobal: true` when the caller mutates role/permission pivot rows *without* going through those methods (see `AccessChangeAnalyzer`, which runs speculative transactional mutations).

## Meeting scope and visibility rules live in models.md
`InstitutionScopeResolver`, `MeetingCompletionService` and `VoteStatisticsCalculator` are all
governed by the governance-scope and public-visibility rules in `.ai/rules/models.md` — read those
before changing any of the three.
