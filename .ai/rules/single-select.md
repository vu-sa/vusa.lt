---
paths:
  - 'resources/js/Components/AdminForms/**,resources/js/Components/RichContent/**,resources/js/Features/Admin/AdminSearch/**,resources/js/Components/ui/single-select/**'
---

# Single Select

## Which picker to reach for (id-select vs SingleSelect vs CollectionSelectDialog)
Three distinct picker patterns exist — pick by list size and whether records are search-indexed. No dedicated `CategorySelect`/generic entity-picker component exists; each form wires its own.

1. **Plain shadcn `<Select>` bound directly to a numeric FK** (`v-model="form.category_id"`, `<SelectItem :value="category.id">`). For a small, controller-dumped list (`Category::all(['id','name'])` etc). Use a `__none__` sentinel item for an optional/nullable FK (shadcn `Select` rejects empty-string values) — see `CalendarForm.vue`'s `categoryIdString` bridge. Reference: `PageForm.vue`, `CalendarForm.vue`.

2. **`SingleSelect`** (`resources/js/Components/ui/single-select/SingleSelect.vue`) — a searchable/virtualized Combobox. Operates on **whole objects**, not a scalar id: `v-model` emits the selected item (or `null`), `label-field`/`value-field` props pick which keys to read. Callers must bridge object ↔ scalar themselves via a `computed({get,set})` — see the "Bridge: SingleSelect operates on full objects..." comment in `TenantForm.vue`, `DutiableForm.vue`, `ReservationResourceForm.vue`. Use when the list is bigger than a few dozen rows or benefits from in-place search, but still isn't backed by full-text search infra.

3. **`CollectionSelectDialog`** (`resources/js/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue`) — multi-select dialog backed by Typesense/Scout search across a searchable collection (news, pages, institutions, calendar events, …). Only for models that are actually search-indexed; use `normalizeHit()` (`Features/Admin/AdminSearch/Utils/searchHitMappers.ts`) to shape hits. See `LinkListOptionsFields.vue`'s pinned-news/pages picker.

**Small, non-searchable lookup lists (e.g. `Category`, ~7 rows total, no `tenant_id`) get no dedicated API/search endpoint** — they're dumped directly as a prop from whichever controller needs them (`Category::query()->get(['id','name','alias'])`), per `QuickLinkController::getCategoryOptions()` / `NavigationController::getCategoryOptions()`'s explicit "not worth a search endpoint" rationale. `Category::name`/`description` are Spatie-translatable; serialized this way they resolve to the current-locale **string** server-side — do NOT type them as `App.Entities.Category` (its generated `name` type is the raw `Array<unknown> | null` translation shape) when the value actually reaching JS is a string.

Precedent for wiring a small global lookup list to many disparate forms without prop-threading: `categories` is now shared globally via `HandleInertiaRequests::share()` (mirroring the existing `tenants` share, `Cache::rememberForever`) and read directly with `usePage().props.categories` — see `RCCategoryAliasSelect.vue`. Prefer this over threading a prop through several component layers when the list is small, global, and needed in more than one deeply-nested place.
