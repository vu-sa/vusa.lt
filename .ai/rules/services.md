---
paths:
  - 'app/Services/**'
---

# Services

## resetCache() no longer flushes the global permission cache by default
`ModelAuthorizer::resetCache($user, bool $flushGlobal = false)` only clears that user's `auth:duties:{id}` cache by default. It used to also call `PermissionRegistrar::forgetCachedPermissions()` unconditionally, which flushed Spatie's shared `spatie.permission.cache` for the whole app on every call — including from `UpdateLastAction` middleware, meaning one user's page view invalidated permission caching app-wide on every authenticated request.

Spatie's own `HasRoles`/`HasPermissions` trait methods (`assignRole`, `givePermissionTo`, `syncPermissions`, ...) and the `Role`/`Permission` models' `RefreshesPermissionCache` hooks already self-flush that shared cache. Only pass `flushGlobal: true` when the caller mutates role/permission pivot rows *without* going through those methods (see `AccessChangeAnalyzer`, which runs speculative transactional mutations).

## FileUsageScanner: vusa.lt-only domain stripping and JSON-escaped LIKE matching
Absolute URLs only count as usages when they are on a vusa.lt host (static/www/tenant subdomains): matching uses the bare "vusa.lt{path}" substring variant — scheme-agnostic, any subdomain. Foreign hosts sharing the path must NOT match in JSON columns: jsonNeedles() anchors path needles at the JSON opening quote, so "https://cdn.example.com/uploads/x.jpg" is not a usage of the local /uploads/x.jpg.

Gotchas: content_parts.json_content is longtext — raw JSON stores "/" as "\/" and MySQL's default LIKE escape char "\" silently un-escapes it. All scanner LIKEs therefore use ESCAPE '|' (single char, works on MySQL and SQLite) via escapeLike(). Never reintroduce a static cache in resolvePrimaryOwnerForContent(): RefreshDatabase reuses auto-increment ids in SQLite, so cached owners leak between tests.

## Meeting scope and visibility rules live in models.md
`InstitutionScopeResolver`, `MeetingCompletionService` and `VoteStatisticsCalculator` are all
governed by the governance-scope and public-visibility rules in `.ai/rules/models.md` — read those
before changing any of the three.
