---
paths:
  - 'resources/js/Components/AdminForms/**,resources/js/Components/RichContent/**,resources/js/Features/Admin/AdminSearch/**,resources/js/Components/ui/single-select/**'
---

# Single Select

## Which picker to reach for (NativeSelect vs FormSegmentedControl vs SingleSelect vs CollectionSelectDialog)
Pick by option count, plain-text vs rich formatting, and whether records are search-indexed:

1. **`FormSegmentedControl` / Radio buttons** (`resources/js/Components/Patterns/FormSegmentedControl.vue`): 2–5 mutually exclusive options on form canvases (e.g. course semester, tenant type, contacts grouping, columns). Clean hairline border with brand-filled active segment.

2. **`NativeSelect`** (`resources/js/Components/ui/native-select/NativeSelect.vue`) or **`TenantSelectField`** (`resources/js/Components/AdminForms/TenantSelectField.vue`): 6–16 plain-text options, or small lookups/FKs (e.g. event type, review course, tenants list, degree type, table row quick-assigns).
   - Renders an accessible, native `<select>` with custom hairline styling, 44px coarse touch target, and Lucide `ChevronDown`.
   - Binds directly to scalar values (`string | number | null | undefined`) — no object bridging, no `__none__` sentinels for nullable fields (empty string maps directly to `null` when a `placeholder` is provided).
   - Supports leading resting icons via `:icon` prop (e.g. `NotificationTypeRow.vue`), while keeping native OS selection menus fast and clean.
   - For tenant FK pickers across admin forms, use `<TenantSelectField v-model="form.tenant_id" />`.

3. **`SingleSelect`** (`resources/js/Components/ui/single-select/SingleSelect.vue`): 17+ options, non-indexed lookups requiring in-place fuzzy searching and virtualization (e.g. large parent navigation tree, multi-tenant global lists).
   - Combobox operating on **whole objects**: `v-model` emits the selected item (or `null`), with `label-field`/`value-field` props. Callers bridge object ↔ scalar via `computed({get,set})`.
   - Use only when the list exceeds 16 options or specifically benefits from instant typing/filtering.

4. **Rich options with descriptions / layout previews**: `VisualOptionSelect` (or shadcn `<Select>` where options inside the dropdown popup require descriptions, rich multi-line content, or complex sub-elements).

5. **`CollectionSelectDialog` / `MultiCollectionSelectDialog`** (`resources/js/Features/Admin/AdminSearch/Components/Select/`): Multi- or single-record selection backed by Typesense/Scout search across full search-indexed collections (news, pages, institutions, calendar events). Use `normalizeHit()` to shape hits.

Small, non-searchable lookup lists (e.g. `EventType`, ~7 rows total, no `tenant_id`) get no dedicated API/search endpoint — they are either shared globally via `HandleInertiaRequests::share()` (e.g. `eventTypes`, mirroring the `tenants` share) and read with `usePage().props.eventTypes`, or passed directly as a prop from whichever controller needs them. `EventType::name` is Spatie-translatable; serialized for inertia, translations resolve to the current-locale **string** server-side when requested in public contexts, or the full translation array in admin interfaces.

For multi-tagging and topic assignment, use `TagMultiSelect.vue` with polymorphic `taggables`.

## Pickers: native where the OS is better, one picker per data kind
Extends the picker rules above.
- Dates, times and ranges use the shared pickers. They go native (`<input type="date|time">`) on coarse pointers via `useCoarsePointer`; on desktop, typing always works alongside the calendar. A moment (date + time: publish time, reservation start/end) is one `DateTimePicker` with `variant="popover"` — calendar, time and „Dabar“ in one trigger, as in `ContentPublishPanel`; a date alone uses `DatePicker`, a time alone `TimePicker`. Calendars start on Monday (the `ui/calendar` and `ui/range-calendar` default).
- Meeting dates near today offer preset chips first (Šiandien · Vakar · Kita data…), as in the ActionWindow `MeetingWhenScreen`. Reservation periods do not.
- By option count: 2–5 → `FormSegmentedControl` or `ToggleGroup`/radio; 6–16 plain text (or resting icon) → `NativeSelect`; options with rich descriptions/preview in dropdown → `VisualOptionSelect` or shadcn `Select`; 17+ → `SingleSelect`; indexed records → `CollectionSelectDialog`; several of many → `MultiSelect` chips; tags → `TagMultiSelect`.
- One picker per data kind, so never a second date picker. The trigger shows readable text plus context, never an id. Optional fields get "Išvalyti". Lithuanian locale: Monday first, 24h.
- No dialog inside a dialog: on mobile, a picker opened from a sheet replaces the sheet's content, with a back action.
