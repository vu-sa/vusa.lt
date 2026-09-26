---
paths:
  - 'resources/js/Components/Patterns/**,resources/js/Constants/**'
---

# Constants

## Statuses and entity types each have one presentation source
- A state reads the same word, role and icon everywhere: collection, record, email, notification. Map every state enum once in `Constants/statuses.ts` and render it with `Patterns/StatusBadge`; never hand-pick a badge colour per page.
- Each entity type has one icon and one `--cat-*` colour in `Constants/entityTypes.ts`, rendered by `EntityTypeMark` (or read with `getEntityTypeDefinition`). Use it the same way in title bands, rows, palette results, notifications, Veikla and breadcrumbs.
- One `EmptyState` (first use teaches what this is and offers the first action; "no results" offers clearing filters), content-shaped skeletons, one `TopProgressBar`. Toasts only report the user's own actions; persistent information is an inline band.
- Dates go through `Utils/dateTime.ts` / `useDateFormatter`: relative when near, absolute in tables and record facts, Europe/Vilnius. `Utils/IntlTime.ts` is deprecated.
