---
paths:
  - 'tests/**'
---

# Tests

## Search indexing is nulled by default in tests
`TestingServiceProvider` binds Scout's `typesense` engine to `NullEngine` for every test by default — no HTTP calls, no Typesense connection needed. ~14 models hardcode Typesense in `searchableUsing()` so `SCOUT_DRIVER=database` (phpunit.xml) doesn't cover them; this was previously costing ~29ms of a ~35ms `makeUser()` fixture (measured).

Call `usesTypesense()` (`tests/Pest.php`) in `beforeEach()` only when a test actually asserts on search results or index state (`::search()->raw()`, `->searchable()`). Most tests that merely create searchable models (News, Page, User, Duty, Institution, ...) don't need it — factories still work fine, they just don't index anything.

Keep fixtures minimal: reuse a seeded tenant (`Tenant::query()->first()`), create the fewest users needed, and never `->count(N)` past the smallest N that exercises the code path (e.g. pagination tests only need one page-size boundary crossed).

## Duty::factory()->has(Institution::factory()->state(...)) silently drops the state override
`Duty::factory()->has(Institution::factory()->state(['tenant_id' => $x]))->create()` does NOT pin the created institution's tenant_id to $x — it ends up with whatever `InstitutionFactory`'s own default (`Tenant::factory()`) produces instead. Confirmed via a throwaway debug test: `Institution::factory()->state([...])->create()` alone works fine, and `Duty::factory()->for($institution)->create()` also works fine, but the `has()` + nested `state()` combination on this `BelongsTo`-aliased relation (`Duty::institutions()`, see the "HACK" comment on Duty.php) discards the override.

Affected files: `tests/Feature/Admin/Content/NewsControllerTest.php`, `tests/Feature/Admin/Content/NewsTagsTest.php`, `tests/Feature/Admin/Resources/ReservationControllerTest.php` — all use this pattern in `beforeEach()`. They still pass today only because they never assert the duty's tenant against the tenant they *thought* they pinned; several tests instead read back the real value via `$user->duties()->first()->institution->tenant`.

Until fixed at the source, don't trust `$this->tenant->id` to equal a `Duty::factory()->has(Institution::factory()->state(['tenant_id' => $this->tenant->id]))`-created duty's actual tenant — read it back via `->duties()->first()->institution->tenant` instead. Prefer `Duty::factory()->for(Institution::factory()->state([...]))->create()` in new tests, which does work correctly.
