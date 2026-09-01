---
paths:
  - 'resources/js/Components/Tables/**,resources/js/Components/ui/data-table/**'
---

# Data Table

## Row-hover reveals need `group` from rowClassName
`ui/table/TableRow.vue` and `DataTable.vue` add no `group` class to `<tr>`, so a cell rendered with `opacity-0 group-hover:opacity-100` (the usual hover-revealed actions menu) stays invisible forever — clickable but unfindable. TaskTable's whole actions dropdown, delete included, was unreachable this way. If a column's cell uses a `group-*` variant, the table must return `group` from its `rowClassName`, and the trigger should also carry `focus-visible:opacity-100` so keyboard users can reach it.
