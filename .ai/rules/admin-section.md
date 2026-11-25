---
paths:
  - 'app/Enums/ModelEnum.php'
  - 'app/Support/MorphMap.php'
  - 'app/Services/AdminNavigation/AdminNavigationCatalog.php'
  - 'app/Policies/ModelPolicy.php'
---

# Adding an admin section

A section for a new model touches about fifteen registries. Do them in this order; the test after
each arrow fails when that step is missed. `QuickLink` is the smallest complete example: grep for
`QuickLink`, `quickLinks` and `quick_link`.

1. **Model, migration, factory** → `MorphMap::MAP` alias (`MorphMapTest`).
2. **`ModelEnum` case**, and its explicit scopes in `ModelEnum::allowedScopes()` (see `.ai/rules/permissions.md`), with granted-vs-excluded record coverage in `PermissionScopeMatrixTest`.
3. **Policy**: `class FooPolicy extends ModelPolicy {}` is enough. The permission resource
   (`foos`) comes from the class name, and the padalinys scope reads the model's `tenants`
   relation, or `tenant` when it has none. Override methods only for custom rules.
4. **`PermissionMatrixPresenter::RESOURCE_AREAS`** entry in navigation order (`PermissionCatalogTest`),
   then `vendor/bin/sail artisan permissions:sync`. Default grants for a role go in its seeder
   (`RoleCommunicationCoordinatorSeeder`, …), optional.
5. **Requests and controller**, each action authorized with `#[Authorize]` (`.ai/rules/controllers.md`)
   → `routes/admin.php`: `Route::resource`, plus `Route::softDeletes` when the controller uses
   `HandlesSoftDeletes` (`SoftDeleteRoutesConventionTest`, `RouteAuthorizationCoverageTest`).
6. **Navigation**: a `Section` in `AdminNavigationCatalog` consuming the policy via `Visibility`
   (never conferring a grant), with its `lang/admin/{lt,en}/shell.php` title and description, plus
   route-ownership and persona coverage (`AdminNavigationCatalogTest`, `TranslationIntegrityTest`).
7. **Entity names**: `lang/admin/{lt,en}/entities.php` with plural forms and gender, used by
   `TranslatesEntityMessages::entityMessage()` (`AdminEntityTranslationsTest`).
8. **Frontend**: run `vendor/bin/sail artisan typescript:transform` for `resources/js/Types/enums.ts`,
   then add icons in `resources/js/Components/icons/model-icons.ts` and the entry in
   `resources/js/Constants/entityTypes.ts` (`typecheck:ratchet`: the registry is a
   `Record<ModelEnum, …>`). Add a route icon in `resources/js/Composables/pageIcons.ts`.
   Index, Create and Edit pages follow `.ai/rules/js-pages-admin.md`.
9. **Only when they apply**: `FileUsageScanner::targets()` for columns that link to uploaded
   files (`tests/Feature/Services/FileUsageScannerTest.php`), and `PublicCacheTags` for models
   rendered on the public site.
10. **Guide page and changelog** (AGENTS.md, "Changelog").
