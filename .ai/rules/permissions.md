---
paths:
  - 'app/Support/Permissions/**'
  - 'app/Services/Permissions/PermissionMatrixPresenter.php'
  - 'app/Policies/**'
  - 'app/Enums/ModelEnum.php'
  - 'app/Actions/Permissions/**'
  - 'app/Console/Commands/*Permissions.php'
  - 'database/seeders/ModelPermissionSeeder.php'
  - 'resources/js/Features/Admin/PermissionMatrix/**'
---

# Permissions

Policies extend `ModelPolicy` and use `HasCommonChecks` for common ability checks. Resolve authorization through `ModelAuthorizer` or the `Permission` facade. Vue reads the shared ability flags through `$page.props.auth.can`.

## The matrix is built from the catalog, not hand-kept
`PermissionCatalog::abilities()` is the one list of what a responsibility can hold: every `ModelEnum` resource × `CRUDEnum` ability × scope, minus what `allowedScopes()` removes (`ModelEnum::allowedScopes()`, `create.own`, `UNGRANTABLE`, permanent deletion on models without a trash) and minus `BaselineAccess::RETIRED_PERMISSIONS`. `ModelEnum::allowedScopes()` is exhaustive with no fallback, so a new model cannot silently fail open to grantable scopes. The seeder, the editor, `permissions:audit`, `permissions:sync` and `UpdateRolePermissionsRequest` all read `PermissionCatalog`.

Labels, workspace grouping and row order are presentation metadata in `PermissionMatrixPresenter::toFrontend()`, which reads `AdminNavigationCatalog::WORKSPACE_KEYS` for workspace order and `PermissionMatrixPresenter::RESOURCE_AREAS` for row order in navigation order.

## A scope is only offered where a policy reads it
- `*` — everywhere. `padalinys` — the tenants of the user's non-ended granting duties; every `padalinys`-scoped model must resolve tenants (`owningTenants`, `tenants`, `tenant`, or a custom policy).
- `own` — ids from the live Duty relation `PermissionCatalog::OWN_RELATIONS` names for the resource (`PermissionCatalog::ownRelation()`), with the editor's cell label in `PermissionMatrixPresenter`. A resource not listed has no `own` cell and resolves to no records, so adding a relation to `Duty` never grants anything. Never add null/placeholder entries to `OWN_RELATIONS`; reconcile unimplemented scopes rather than seeding dead grants. Reservations stay out: participants already manage theirs, and `Duty::reservations()` reaches every past holder's.
- `viewAny` and `create` only look at `.padalinys` (`HasCommonChecks`). `create.own` therefore exists only where the policy reads it and the resource is listed in `OWN_CREATE`: `meetings` (`MeetingPolicy::createFor`) and `documents` (`DocumentPolicy::create`; `own` = files of the duty's institution, for bodies like Revizijos komisija inside the main padalinys). Anything else must read it in its own policy first.
- `PermissionCatalog::RESERVED` (`supportRequests`) is strictly unseeded and ungrantable; it only prevents `permissions:sync` from sweeping a super-admin-only policy's name. Reconcile reserved entries when touching their policies.

## A term grants access before it starts, and nothing else
A future-dated term grants its permissions immediately, on purpose: `User::current_or_upcoming_duties` has no start check, so an incoming holder can prepare. It carries no responsibility until it starts. Contacts, mailing lists, tasks and any mail use started-and-not-ended (`current_duties`, `Dutiable::activeOn()`); `Dutiable::notEnded()` counts future terms: seats, quotas and upcoming reps, never contacts or mail. Tests: `ScheduledDutyAuthorizationTest`, `InstitutionActivityRequestControllerTest`.

## Policies that bind super admins too
`Gate::before` in `AuthServiceProvider` grants a super admin everything except the models it lists
(`Goal`, `LearningStep`), whose policies enforce invariants. Add a model there when its policy must hold for everyone.

## Baseline versus grant
What every signed-in member may do belongs in the **policy**, with a sentence in `lang/admin/{lt,en}/access.php` (`baseline.<resource>`, add the resource to `BaselineAccess::RESOURCES`) shown as the lock hint in the matrix. If the permission would grant nothing beyond it, add the name to `BaselineAccess::RETIRED_PERMISSIONS` (the cell shows "Visi nariai") or, for a single dead combination, to `PermissionCatalog::UNGRANTABLE` (the cell shows "–"). Describe every automatic grant a policy gives (own record, assignee, participant, author); the editor is where people learn what a responsibility adds.

## Adding or removing a permission
1. New model: the whole list is in `.ai/rules/admin-section.md`; for permissions it is a `ModelEnum` case with explicit scopes in `ModelEnum::allowedScopes()`, a policy extending `ModelPolicy` (resource name derived from the class name), and a `PermissionMatrixPresenter::RESOURCE_AREAS` entry in navigation order. Non-model capability (like `settings`): `PermissionCatalog::STANDALONE`.
2. Restrict scopes to what the policy actually reads (`ModelEnum::allowedScopes()`, `UNGRANTABLE`) and add a record-level test in `PermissionScopeMatrixTest` with an example record each supported scope grants and one it excludes. Label the resource through `getEntityTypeDefinition` or `responsibilities.matrix.resources.<key>`.
3. Run `vendor/bin/sail artisan permissions:sync --dry-run`, then without the flag. It creates the missing rows, and detaches and deletes every stored name the catalog no longer offers, listing who held it. Existing rows keep their ids and holders; a second run changes nothing. `deployment:run` runs it after `migrate`, so no migration is needed for catalog changes.
4. Removing a permission is the same: take it out of the catalog and sync. Deleting a permission a responsibility holds removes real access, so read the "Held by" table before running it on production.
5. `PermissionCatalogTest` fails when app code names a permission nobody can grant, when the `own` cells change, when an `OWN_RELATIONS` entry is missing on `Duty` or lacks an editor label, or when a `padalinys` model lacks a tenant path; `OwnScopeTest` checks that an unlisted `own` reaches nothing.
