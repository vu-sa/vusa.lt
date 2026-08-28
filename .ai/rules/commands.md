---
paths:
  - 'app/Services/Typesense/**, app/Console/Commands/GenerateTypesenseSearchKey.php'
---

# Commands

## Regenerate the Typesense search-only key after renaming public collections
Typesense API keys bake in the collection list at creation. Renaming/adding a public collection (e.g. news → public_news) does NOT update the existing key — searches against the new name get 401 while old names keep working, so only some pages break. After any change to TypesenseCollectionConfig::PUBLIC_COLLECTIONS, regenerate with `sail artisan typesense:generate-search-key` (confirm prompt; pipe `y` when non-interactive) and do the same on production.
