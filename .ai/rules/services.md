---
paths:
  - 'app/Services/**'
---

# Services

## resetCache() no longer flushes the global permission cache by default
`ModelAuthorizer::resetCache($user, bool $flushGlobal = false)` only clears that user's `auth:duties:{id}` cache by default. It used to also call `PermissionRegistrar::forgetCachedPermissions()` unconditionally, which flushed Spatie's shared `spatie.permission.cache` for the whole app on every call — including from `UpdateLastAction` middleware, meaning one user's page view invalidated permission caching app-wide on every authenticated request.

Spatie's own `HasRoles`/`HasPermissions` trait methods (`assignRole`, `givePermissionTo`, `syncPermissions`, ...) and the `Role`/`Permission` models' `RefreshesPermissionCache` hooks already self-flush that shared cache. Only pass `flushGlobal: true` when the caller mutates role/permission pivot rows *without* going through those methods (see `AccessChangeAnalyzer`, which runs speculative transactional mutations).

## FileUsageScanner: decode the stored value, never enumerate needle encodings
One upload path reaches the database in many forms: raw in plain columns, `\/` and `\uXXXX` in Laravel-cast JSON, `%20` and `&#43;`/`&amp;`/`&#039;` in sanitized HTML (symfony UrlSanitizer), percent-encoded when pasted from a browser, NFC or NFD. The old scanner built LIKE needles for each combination and still missed `%20`, so files with spaces in rich text read as "safe to delete".

The scanner now works in two stages. Stage 1 is SQL: it ANDs `LIKE ? ESCAPE '|'` on the path's `[A-Za-z0-9._-]{2,}` runs (`FileReferenceMatcher::coarseNeedles()`), which every encoding above leaves intact. Stage 2 is PHP: `FileReferenceMatcher::matches()` decodes each candidate (JSON leaves → HTML entities → `rawurldecode`, NFC) and matches the whole `/uploads/files/<path>` with boundaries.
- To support a new storage form, extend the decoding in `FileReferenceMatcher`. Never add needle variants. `tests/Unit/Services/FileReferenceMatcherTest.php` asserts the coarse needles survive every positive form.
- Every column that can hold an upload URL belongs in `FileUsageScanner::targets()`, including every ContentPart type (no type filter). `tests/Feature/Services/FileUsageScannerTest.php` creates a reference in each registered column and fails if a column does not exist.
- Only vusa.lt hosts count; a foreign host serving the same path does not. Bare filenames never count.
- Legacy `/uploads/<path>` counts only when no file exists at `public/<path>` (RewriteUploadsUrl redirects only then).
- No result cache: a cached "safe" outlives a new reference. Do not swallow query exceptions either, since an empty result reads as "safe to delete".
- Never reintroduce a static cache in owner resolution: RefreshDatabase reuses auto-increment ids in SQLite.

## Meeting scope and visibility rules live in models.md
`InstitutionScopeResolver`, `MeetingCompletionService` and `VoteStatisticsCalculator` are all
governed by the governance-scope and public-visibility rules in `.ai/rules/models.md` — read those
before changing any of the three.

## ModelAuthorizer has no ambient state — every read takes (User, permission)
`ModelAuthorizer` used to be a request **singleton** whose public surface was whatever the previous caller left behind: `getTenants()` with no argument resolved against `lastCheckedPermission`, and `$isAllScope` / `$permissableDuties` held the last check's result. Worse, `checkAllRoleables()` reset `permissableDuties` on every call but **never** `isAllScope`, so the first `*`-scoped permission resolved in a request latched that flag true for every later resolution and the memo stored the latched value. `getTenants()` read the flag *before* re-checking its argument, so passing an explicit permission did not help. Two seeded roles made this reachable: `Resource Manager` (`resources.read.*`) gained all-tenant reservation approval, and a `Global Communication Coordinator` (`tags.update.*`) gained all-tenant scope everywhere their tenant-scoped role granted anything.

The API is now `scope($user, $permission)` returning an immutable `App\Services\Authorization\PermissionScope` (`granted`, `isAllScope`, `duties`, `tenants`), with `allows()` / `tenants()` / `duties()` as thin readers. `forUser()`, `check()`, `checkAllRoleables()`, `getTenants()`, `getPermissableDuties()` and the public properties are gone, and the binding is `scoped()` rather than `singleton()` so the memo cannot outlive a request under Octane. `tests/Feature/System/AuthorizerApiConventionTest.php` fails if any of those names reappear under `app/`.

Two rules follow from this:
- **A permission the actor does not hold resolves to zero tenants.** There is no "fall back to the tenants they hold any duty in" — that fallback was the escalation path in issue #621. Do not reintroduce one; deny instead.
- **Never cache or pass around a resolved scope as if it were the actor's scope generally.** It answers one permission. Resolve each permission you actually mean; the memo makes that free.

Tenant access is derived from *current* duties only (`loadDuties()`), never from `User::tenants()`, which is a HasManyDeep over every duty the user has ever held.
