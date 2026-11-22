---
paths:
  - 'resources/js/**'
---

# Frontend

`npm run typecheck:ratchet` blocks CI on new errors; don't chase unrelated existing findings.
After fixes, run `npm run typecheck:baseline` to lock them in. Never grow it to pass CI;
an intentional `--allow-increase` requires justification in the PR.

## One HTTP convention
Blocking lint (`no-restricted-globals` / `no-restricted-syntax` in `eslint.config.mjs`):
- JSON calls to our own endpoints → `useApi` (reactive read), `useApiMutation` (one fixed action) or `apiRequest()` (imperative: any URL, concurrent, `FormData`, `keepalive`, status-aware) from `@/Composables/useApi`.
- A form with bound fields and validation errors outside page navigation → Inertia `useHttp`.
- Raw `fetch`, XHR or VueUse `useFetch` only for Typesense, external services, static files and upload progress — the lint allowlist.

## TypeScript and route helpers
Prefer concrete types or `unknown` for new code and replace nearby `any` when the shape is known. Import `ApiResponse<T>` from `@/Types/api.d`. Available globals include `PaginatedModels<T>`, `CalendarEventForm` and `InstitutionForm`.
Use JSDoc for non-obvious behaviour, deprecations and external workarounds. Leave signature details to the types.
Use Ziggy's `route()` helper. `vite.config.mts` generates its types. For bilingual UI strings, see `.ai/rules/lang.md`.

## Icons follow their surface
Use Lucide (`@lucide/vue`) for admin, Fluent (`~icons/fluent/x24-regular`) for public, and Simple Icons (`~icons/simple-icons/facebook`) for brand and social logos. Use `@iconify/vue` for a name supplied at runtime.
Use `@/Components/icons` for shared model icon lookups in admin code such as the command palette and notifications.

## Utility classes stay with the component
Use Tailwind utilities directly on elements. Group long class lists in arrays by purpose. Keep local styles with the component and reserve `@apply` for cases such as line clamping and keyframes. Use theme-aware tokens or pair colour overrides with their `dark:` variants.

## Dates have one formatter
Dates go through `Utils/dateTime.ts` / `useDateFormatter`: relative when near (short form, „prieš 2 d.“), absolute in tables and record facts, Europe/Vilnius. Pass backend strings straight in — `parseDate` reads a zone-less `Y-m-d H:i` as Vilnius wall-clock time, where `new Date()` would read it in the browser's zone. Date-only values (due dates) use `formatNearDate(…, { granularity: 'day' })`; shapes the named formatters lack use `formatDateWith`. Picker output goes back to the backend through `toNaiveDateTimeString`. date-fns is for arithmetic only, and ESLint bans its `format*` imports.

## Drop subtitles that only restate the title
A subtitle, description or hint (choice-list descriptions, dialog/screen subtitles, card captions, field hints) must add information the title doesn't already give. If it would only restate the title ("Pertrauka" → "Posėdžio pertrauka."), leave it out entirely, with no filler text and no empty slot, even when its siblings have one. Applies to admin and public surfaces.

## Clear Inertia form dirtiness before a deliberate visit
For forms with an unsaved-changes guard, call `form.defaults()` before `form.submit()` or `router.visit()` when the visit should proceed. It clears `isDirty` synchronously. `form.defaults(data)` leaves recalculation to the watcher, after the router's `before` event.

## Saves on a page with deferred props pass `except`
A save that redirects `back()` reloads the whole page, and a full reload drops deferred props, so each `<Deferred>` falls back to its skeleton and reloads. The page gets shorter, and `preserveScroll` restores the position against that shorter page and loses it. A save, delete or link request (`form.patch`, `router.post/delete`) on a page with deferred props passes `except: [<deferred keys the request can't change>]`. Precedent: `useAgendaItemAutosave(..., ['goalLinks'])` in `ShowAgendaItem.vue`.

## Shadcn Checkbox binds `modelValue`
Bind a Checkbox with `v-model` / `:model-value`, never `:checked` + `@update:checked`: reka-ui's `CheckboxRoot` exposes only `modelValue`, so the old pair silently does nothing.
