---
paths:
  - 'app/Services/Typesense/**'
---

# Typesense

## Typesense narrows, the policy decides
A search list may only narrow what the user sees; opening any record is always authorized live by its policy (view / viewSummary / follow).
- Index facts, never settings-dependent conclusions: store e.g. `institution_type_ids`, `is_active`, `type_ids`, and build the scoped key's public clause from current settings in TypesenseScopedKeyService (`public_rows`). A settings change then needs no reindex — the cached key carries `visibility_version`.
- A new fact embedded from a relation needs a reindex path: relation changes go through `auditRelationChange()` → `SearchRelationChanged` → `SyncRelationSearchIndex`.
- Keep `tests/Feature/Admin/Search/SearchVisibilityParityTest.php` green: rows a key admits must equal the rows `viewSummary` allows. Extend it when adding a visibility rule.
