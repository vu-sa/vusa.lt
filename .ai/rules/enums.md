---
paths:
  - 'app/Enums/**'
---

# Enums

## Enum backing values must be what the database stores
`spatie/laravel-typescript-transformer` exports every enum's *backing value* to `resources/js/Types/enums.ts`. NewsLayoutEnum and PageLayoutEnum used to back their cases with `'MODERN'`/`'DEFAULT'` while the column stored the lowercase `label()`, so the generated TS enum silently never matched `news.layout` — and `::from()` would have thrown. Both are fixed; keep the backing value equal to the persisted string.

Run `sail artisan typescript:transform` after touching an enum, and check the diff.

Allowlists that resolve a class from user input belong on the enum (`AllowedFileablesEnum::classFor()`, `AllowedRelationshipablesEnum::modelClasses()`), not in a namespace-level `const` — there used to be two separate `ALLOWED_FILEABLE_TYPES` constants in different namespaces, free to drift.
