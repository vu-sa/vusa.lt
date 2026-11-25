---
paths:
  - 'resources/js/Components/Tables/**'
  - 'resources/js/Components/ui/data-table/**'
---

# Data Table

## Embedded tables
Use `SimpleDataTable` for local rows, including facets supplied through `#filters`. Collection pages use
`useCollectionTable` and their collection source for server-side sorting, filtering and pagination.

## Row-hover reveals need a row group
`DataTable.vue` adds `group` to its rows. Add it to rows built directly with `ui/table/TableRow.vue`
when a cell uses `group-hover:opacity-100`. Give the trigger `focus-visible:opacity-100` so keyboard
users can reach it.
