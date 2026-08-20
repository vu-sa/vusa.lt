---
paths:
  - 'tests/**'
---

# Tests

## Search indexing is nulled by default in tests
`TestingServiceProvider` binds Scout's `typesense` engine to `NullEngine` for every test by default — no HTTP calls, no Typesense connection needed. ~14 models hardcode Typesense in `searchableUsing()` so `SCOUT_DRIVER=database` (phpunit.xml) doesn't cover them; this was previously costing ~29ms of a ~35ms `makeUser()` fixture (measured).

Call `usesTypesense()` (`tests/Pest.php`) in `beforeEach()` only when a test actually asserts on search results or index state (`::search()->raw()`, `->searchable()`). Most tests that merely create searchable models (News, Page, User, Duty, Institution, ...) don't need it — factories still work fine, they just don't index anything.

Keep fixtures minimal: reuse a seeded tenant (`Tenant::query()->first()`), create the fewest users needed, and never `->count(N)` past the smallest N that exercises the code path (e.g. pagination tests only need one page-size boundary crossed).
