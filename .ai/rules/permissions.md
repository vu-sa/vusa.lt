---
paths:
  - 'app/Support/Permissions/**'
  - 'app/Policies/**'
  - 'app/Enums/ModelEnum.php'
  - 'app/Actions/Permissions/**'
  - 'app/Console/Commands/*Permissions.php'
  - 'database/seeders/ModelPermissionSeeder.php'
  - 'resources/js/Features/Admin/PermissionMatrix/**'
---

# Permissions

## The matrix is built from the catalog, not hand-kept
`PermissionCatalog::abilities()` is the one list of what a responsibility can hold: every `ModelEnum` resource × `CRUDEnum` ability × scope, minus what `allowedScopes()` removes (the enum's per-model scopes, `create.own`, `UNGRANTABLE`, permanent deletion on models without a trash) and minus `BaselineAccess::RETIRED_PERMISSIONS`. `toFrontend()` sends it to `PermissionMatrix.vue`, grouped by workspace in `RESOURCE_AREAS` order — keep that list in `AdminNavigationCatalog` order. The seeder, the editor, `permissions:audit` and `permissions:sync` all read it.

## A scope is only offered where a policy reads it
- `*` — everywhere. `padalinys` — the tenants of the user's non-ended granting duties.
- `own` — ids from the **same-named relation on `Duty`** (`ModelAuthorizer::resolveOwnModelIds`). A model with no such relation gets no `own` cell; offering one grants nothing. Check the relation before allowing `own`.
- `viewAny` and `create` only look at `.padalinys` (`HasCommonChecks`). `create.own` therefore exists for `meetings` alone (`MeetingPolicy::createFor`, listed in `OWN_CREATE`). Anything else must read it in its own policy first.
- A scope reserved for later (`goals.*.own`) is allowed in `ModelEnum::getAllowedScopes` with a comment saying so.

## Baseline versus grant
What every signed-in member may do belongs in the **policy**, with a sentence in `lang/admin/{lt,en}/access.php` (`baseline.<resource>`, add the resource to `BaselineAccess::RESOURCES`) shown as the lock hint in the matrix. If the permission would grant nothing beyond it, add the name to `BaselineAccess::RETIRED_PERMISSIONS` (the cell shows "Visi nariai") or, for a single dead combination, to `PermissionCatalog::UNGRANTABLE` (the cell shows "–"). Describe every automatic grant a policy gives (own record, assignee, participant, author); the editor is where people learn what a responsibility adds.

## Adding or removing a permission
1. New model: `ModelEnum` case, a policy extending `ModelPolicy` with `pluralModelName`, and a `RESOURCE_AREAS` entry in navigation order. Non-model capability (like `settings`): `PermissionCatalog::STANDALONE`.
2. Restrict scopes to what the policy actually reads (`ModelEnum::getAllowedScopes`, `UNGRANTABLE`). Label the resource through `getEntityTypeDefinition` or `responsibilities.matrix.resources.<key>`.
3. Run `vendor/bin/sail artisan permissions:sync --dry-run`, then without the flag. It creates the missing rows, and detaches and deletes every stored name the catalog no longer offers, listing who held it. Existing rows keep their ids and holders; a second run changes nothing. `deployment:run` runs it after `migrate`, so no migration is needed for catalog changes.
4. Removing a permission is the same: take it out of the catalog and sync. Deleting a permission a responsibility holds removes real access, so read the "Held by" table before running it on production.
5. `PermissionCatalogTest` fails when app code names a permission nobody can grant, and when an `own` cell has no `Duty` relation behind it.
