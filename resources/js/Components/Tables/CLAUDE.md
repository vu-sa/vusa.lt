# Tables — AI guidance

## Decision tree

```
A list of many things of one kind (an admin index)?   → Layouts/CollectionPage + a collection source
A small table inside another page (a record, a sheet)? → Tables/SimpleDataTable
```

There is no third option. `IndexTablePage` and `ServerDataTable` were removed; don't recreate them.

## CollectionPage: one design, three sources

`CollectionPage` owns the whole anatomy (title band, filter band, rows · table · preview, load more,
selection bar, trash) and never learns where rows come from. Pick the source by the data:

| Source (`@/Composables/useCollectionSource`) | Use for | Backend |
|---|---|---|
| `useTypesenseCollectionSource` | big searchable collections already in Typesense (pages, news, institutions, meetings, documents, resources) | scoped search key; the controller sends no rows |
| `useDatabaseCollectionSource` | workflow collections with server facets (reservations, problems, forms, tags, calendar, users, duties) | Inertia first page + an `Api\Admin\*ApiController::index` twin via `HasTanstackTables` |
| `useLocalCollectionSource` | short config lists sent whole as a prop (roles, permissions, types, tenants, study programmes) | `->get()`, no paging |

**Trash is a filter, not a page.** Pass `:trash="{ count: deletedCount, active: isTrashView() }"`;
the control visits `?showDeleted=true` on the same route. Typesense never indexes deleted rows, so
Typesense pages switch with `useTrashAwareSource(live, () => useTrashCollectionSource('<collection>'))`,
which reads `api.v1.admin.trash.index` — rows come back as the model's `toSearchableArray()`, so
the same cells render them. Database and local sources just reload with `showDeleted`.

**TanStack is the engine inside `CollectionResults`** (`useCollectionTable`): selection, header sort,
column visibility. It runs in manual mode — the source owns search, facets, sort and paging. Pages
describe columns as `CollectionColumn` (`sortField` makes a header sortable when the source offers
both directions; `pinned` keeps a column out of the "Stulpeliai" menu) and render cells through the
`#cell` slot. For bulk work pass `selectable` (and `can-select` for per-row rules) and fill
`#bulk-actions`; bind `v-model:selection` only when the page must clear it itself.

Shift-click on a row checkbox selects the range since the last toggled row (`toggleRow` in
`useCollectionTable`).

**Optimistic changes**: Scout syncs through the queue, so Typesense lags a save. Change rows with
`source.patchItems(ids, patch)` / `source.hideItems(ids)` before the request and call the
returned undo in `onError`; the overlay outlives later searches. Draft ⇄ published content
(`IndexPages`, `IndexNews`) goes through `useCollectionPublishing` + `CollectionPublishActions`,
backed by `<prefix>.bulkStatus` / `<prefix>.bulkDestroy` and `Requests/Content/BulkContentRequest`.

**Shared cells**: `CollectionPrimaryCell` (bold title + one quiet sub-line), `CollectionStatusMenu`
(a `StatusBadge` that opens a status picker when `editable`), `CollectionRowActions`
(square bordered icon actions, one labelled), `StatusBadge`. Row actions for edit / delete /
duplicate / restore / permanent delete come from `useCollectionRecordActions` with
`CollectionConfirmAction` for the dialog.

## SimpleDataTable

Client-side TanStack table for embedded use (< ~100 rows): `:data` + `:columns` (`ColumnDef[]`).
It renders through `ui/table`, so it shares the collection table's look.

## Gotchas

- `Checkbox` binds with `v-model` / `model-value`, never `v-model:checked`.
- A TanStack column without an accessor cannot sort — `useCollectionTable` adds one for you.
- Hover-revealed actions need `group` on the row and `focus-visible:opacity-100` (see
  `.ai/rules/data-table.md`); collection row actions are always visible instead.
