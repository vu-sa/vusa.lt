---
paths:
  - 'resources/js/Components/AdminForms/**,resources/js/Components/RichContent/**,resources/js/Features/Admin/AdminSearch/**,resources/js/Components/ui/single-select/**'
---

# Single Select

## Which picker to reach for (id-select vs SingleSelect vs CollectionSelectDialog)
Three distinct picker patterns exist — pick by list size and whether records are search-indexed.

1. **Plain shadcn `<Select>` bound directly to a numeric FK** (`v-model="form.event_type_id"`, `<SelectItem :value="eventType.id">`). For a small, controller-dumped list or globally shared dictionary (`eventTypes`, etc.). Use a `__none__` sentinel item for an optional/nullable FK (shadcn `Select` rejects empty-string values) — see `CalendarForm.vue`'s `eventTypeIdString` bridge. Reference: `CalendarForm.vue`.

2. **`SingleSelect`** (`resources/js/Components/ui/single-select/SingleSelect.vue`) — a searchable/virtualized Combobox. Operates on **whole objects**, not a scalar id: `v-model` emits the selected item (or `null`), `label-field`/`value-field` props pick which keys to read. Callers must bridge object ↔ scalar themselves via a `computed({get,set})` — see the "Bridge: SingleSelect operates on full objects..." comment in `TenantForm.vue`, `DutiableForm.vue`, `ReservationResourceForm.vue`. Use when the list is bigger than a few dozen rows or benefits from in-place search, but still isn't backed by full-text search infra.

3. **`CollectionSelectDialog`** (`resources/js/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue`) — multi-select dialog backed by Typesense/Scout search across a searchable collection (news, pages, institutions, calendar events, …). Only for models that are actually search-indexed; use `normalizeHit()` (`Features/Admin/AdminSearch/Utils/searchHitMappers.ts`) to shape hits. See `LinkListOptionsFields.vue`'s pinned-news/pages picker.

**Small, non-searchable lookup lists (e.g. `EventType`, ~7 rows total, no `tenant_id`) get no dedicated API/search endpoint** — they are either shared globally via `HandleInertiaRequests::share()` (e.g. `eventTypes`, mirroring the `tenants` share) and read with `usePage().props.eventTypes`, or passed directly as a prop from whichever controller needs them. `EventType::name` is Spatie-translatable; serialized for inertia, translations resolve to the current-locale **string** server-side when requested in public contexts, or the full translation array in admin interfaces.

For multi-tagging and topic assignment, use `TagMultiSelect.vue` with polymorphic `taggables`.

## Pickers: native where the OS is better, one picker per data kind
Extends the three-picker rule above.
- Dates, times and ranges use the shared pickers. They go native (`<input type="date|time">`) on coarse pointers via `useCoarsePointer`; on desktop, typing always works alongside the calendar. A moment (date + time: publish time, reservation start/end) is one `DateTimePicker` with `variant="popover"` — calendar, time and „Dabar“ in one trigger, as in `ContentPublishPanel`; a date alone uses `DatePicker`, a time alone `TimePicker`. Calendars start on Monday (the `ui/calendar` and `ui/range-calendar` default).
- Meeting dates near today offer preset chips first (Šiandien · Vakar · Kita data…), as in the ActionWindow `MeetingWhenScreen`. Reservation periods do not.
- By option count: 2–5 → `ToggleGroup`/radio; 6–15 plain text → native `<select>`; options with icons or descriptions → shadcn `Select`; 16+ → `SingleSelect`; indexed records → `CollectionSelectDialog`; several of many → `MultiSelect` chips; tags → `TagMultiSelect`.
- One picker per data kind, so never a second date picker. The trigger shows readable text plus context, never an id. Optional fields get "Išvalyti". Lithuanian locale: Monday first, 24h.
- No dialog inside a dialog: on mobile, a picker opened from a sheet replaces the sheet's content, with a back action.
