---
paths:
  - 'resources/js/**'
---

# Frontend

## Dates have one formatter
Dates go through `Utils/dateTime.ts` / `useDateFormatter`: relative when near, absolute in tables and record facts, Europe/Vilnius. `Utils/IntlTime.ts` is deprecated.
