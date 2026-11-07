---
paths:
  - 'resources/js/**'
---

# Frontend

`npm run typecheck:ratchet` blocks CI on new errors; don't chase unrelated existing findings.
After fixes, run `npm run typecheck:baseline` to lock them in. Never grow it to pass CI;
an intentional `--allow-increase` requires justification in the PR.

## Dates have one formatter
Dates go through `Utils/dateTime.ts` / `useDateFormatter`: relative when near (short form, „prieš 2 d.“), absolute in tables and record facts, Europe/Vilnius. Pass backend strings straight in — `parseDate` reads a zone-less `Y-m-d H:i` as Vilnius wall-clock time, where `new Date()` would read it in the browser's zone. Date-only values (due dates) use `formatNearDate(…, { granularity: 'day' })`; shapes the named formatters lack use `formatDateWith`. Picker output goes back to the backend through `toNaiveDateTimeString`. date-fns is for arithmetic only, and ESLint bans its `format*` imports.

## Drop subtitles that only restate the title
A subtitle, description or hint (choice-list descriptions, dialog/screen subtitles, card captions, field hints) must add information the title doesn't already give. If it would only restate the title ("Pertrauka" → "Posėdžio pertrauka."), leave it out entirely, with no filler text and no empty slot, even when its siblings have one. Applies to admin and public surfaces.

## Saves on a page with deferred props pass `except`
A save that redirects `back()` reloads the whole page, and a full reload drops deferred props, so each `<Deferred>` falls back to its skeleton and reloads. The page gets shorter, and `preserveScroll` restores the position against that shorter page and loses it. A save, delete or link request (`form.patch`, `router.post/delete`) on a page with deferred props passes `except: [<deferred keys the request can't change>]`. Precedent: `useAgendaItemAutosave(..., ['goalLinks'])` in `ShowAgendaItem.vue`.
