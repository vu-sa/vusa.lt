---
paths:
  - 'app/Models/**'
  - app/Models/Tenant.php
---

# Models

## Public* mirror models index via direct ->searchable() calls, not the observer
`Institution`, `News`, `Page` (and their `Public*` mirrors) sync their public search index from a `saved`/`deleted` hook that calls `PublicX::query()->find($id)->searchable()` directly (see `Institution::booted()`, `News::syncPublicSearchIndex()`, `Page::syncPublicSearchIndex()`). This bypasses Scout's `ModelObserver` entirely, so `Model::disableSearchSyncing()` does NOT suppress it.

To actually stop indexing (e.g. in a test), swap the Scout engine itself — see `TestingServiceProvider` / `usesTypesense()` in `tests/Pest.php`, which null the engine at the `EngineManager` level so both the observer path and these direct calls are covered.

## Tenant type is a cast enum — do not compare against strings
`tenants.type` is cast to `App\Enums\TenantType`, so `$tenant->type === 'pagrindinis'` is always false. Use `TenantType::Pagrindinis` / `$tenant->isMain()`.

Helpers to reach for instead of re-deriving:
- `Tenant::main()` — the single central-office tenant (was hand-written in six places).
- `Tenant::query()->representational()` — everything except PKP. Inside a `whereHas()` closure PHPStan only sees `Builder<Model>`, so use `whereIn('type', TenantType::representationalValues())` there.

`GetTenantsForUpserts` deliberately emits `$tenant->type?->value`: its array shape goes straight to Inertia and is compared against plain strings by several callers.
