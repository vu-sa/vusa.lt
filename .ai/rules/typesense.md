---
paths:
  - 'app/Services/Typesense/**'
  - 'app/Console/Commands/GenerateTypesenseSearchKey.php'
---

# Typesense

## Typesense narrows, the policy decides
A search list may only narrow what the user sees; opening any record is always authorized live by its policy (view / viewSummary / follow).
- Index facts, never settings-dependent conclusions: store e.g. `institution_type_ids`, `is_active`, `type_ids`, and build the scoped key's public clause from current settings in TypesenseScopedKeyService (`public_rows`). A settings change then needs no reindex — the cached key carries `visibility_version`.
- A new fact embedded from a relation needs a reindex path: relation changes go through `auditRelationChange()` → `SearchRelationChanged` → `SyncRelationSearchIndex`.
- Keep `tests/Feature/Admin/Search/SearchVisibilityParityTest.php` green: rows a key admits must equal the rows `viewSummary` allows. Extend it when adding a visibility rule.

## Regenerate the Typesense search-only key after renaming public collections
Typesense API keys bake in the collection list at creation. Renaming/adding a public collection (e.g. news → public_news) does NOT update the existing key — searches against the new name get 401 while old names keep working, so only some pages break. After any change to TypesenseCollectionConfig::PUBLIC_COLLECTIONS, regenerate with `sail artisan typesense:generate-search-key` (confirm prompt; pipe `y` when non-interactive) and do the same on production.
