---
paths:
  - 'app/Models/**'
---

# Models

## Public* mirror models index via direct ->searchable() calls, not the observer
`Institution`, `News`, `Page` (and their `Public*` mirrors) sync their public search index from a `saved`/`deleted` hook that calls `PublicX::query()->find($id)->searchable()` directly (see `Institution::booted()`, `News::syncPublicSearchIndex()`, `Page::syncPublicSearchIndex()`). This bypasses Scout's `ModelObserver` entirely, so `Model::disableSearchSyncing()` does NOT suppress it.

To actually stop indexing (e.g. in a test), swap the Scout engine itself — see `TestingServiceProvider` / `usesTypesense()` in `tests/Pest.php`, which null the engine at the `EngineManager` level so both the observer path and these direct calls are covered.
