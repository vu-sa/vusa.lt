---
paths:
  - 'resources/js/Pages/Admin/**'
---

# Js Pages Admin

## Every admin page is one of seven page types
Pick the shell by what the page is for, then build it on that shell:
- Overview (starts a day or workspace): `Layouts/OverviewPage`.
- Collection (many of one thing): `Layouts/CollectionPage` + `useDatabaseCollectionSource` / `useTypesenseCollectionSource`.
- Record (one thing with relations, status or discussion): `Layouts/RecordPage`.
- Form (the content itself, e.g. news or pages; the editor is canonical): `Layouts/FormPage` + `FormSection`.
- Sheet form (≤ ~4 fields, no relations): `Patterns/SheetForm` over its collection.
- Guided flow (occasional task, ≤ ~6 decisions): an ActionWindow flow.
- Workbench (canvas: rich editor, Gantt, graph, navigation builder): full-bleed, may have a local side panel.
One canonical page per record: work objects (meeting, institution, duty, user, problem, reservation, form, support request) → record page; content (news, pages, calendar, banners) → its editor. Lists link only there.
Dialog vs sheet vs page: confirming → dialog; small create/edit or preview → sheet (bottom sheet below `md`); shareable or longer than one screen → page.

## Collection and record anatomy
Collection (`CollectionPage`; the v0 `/sistema/puslapiai` prototype and public `ShowDocuments.vue` are the reference): title band (brand eyebrow with the entity icon, `u-display` title, lead, one `lg` create) → quick-filter chips → a hairline filter band: search · Filtrai (count badge) · Rikiuoti · Ištrinti (N) · view toggle (Eilutės · Lentelė · Peržiūra; rows only below `md`, preview from `xl`) → active chips + "Išvalyti visus" · "Rasta N" → results → "Rodyti daugiau" (50 at a time, restored on back). The URL carries filters, sort, view and `?item=`. Tenant scope is a visible filter. The preview pane is the default only for queues. Selection is TanStack's (`selectable` + `#bulk-actions`, bottom-pinned bar); the most common status change is an optimistic row action. Trash is a filter over the same page and source contract — never a separate trash page or table — and soft delete gets an undo toast.
Record (`RecordPage`): identity band (type eyebrow, identity anchor — photo, logo, date plate or entity mark — title, status, ONE primary action, ⋯ for edit/merge/delete/copy link) → key-facts strip (3–6) → sections (tabs when ≥3) → Veikla (`RecordActivity`: comments + changes) last. Relations are managed here. Opened from a filtered list → ‹ 3 / 24 ›. Merge is a ⋯ / bulk action, never a page.

## Admin visual budget, distinctness and responsiveness
- Budgets: uppercase is for the few things that anchor a screen — the primary creation button (a region's one brand-filled action, a page's `lg` primary, dialog primary confirms), section titles, form field labels, eyebrows, workspace tabs, page headlines (`u-display`), and content publishing options (`contentStatuses` / `bannerStatuses`). Every other button — outline, ghost, secondary, row actions, filter or preset chips, and admin toolbar controls (Filtrai, Rikiuoti, Išvalyti visus) — and every other tag, chip, resource label (literal name), or status badge is sentence case: pass `voice="sentence"` to `ui/button` and `controlVariants` rather than restyling per page. One brand-filled action per region. Minimum 12px text (11px eyebrows and table header labels).
- Structure comes from hairlines and whitespace. No shadows, gradients, `rounded-*` or cards in cards; a bordered box means "an object you act on". Titled panels are `SectionCard`, not raw `ui/card`. Empty sections collapse to one line.
- Distinctness comes from identity (anchor + entity mark in every title band), a stated location (eyebrow "ViSAK · POSĖDŽIAI", tab title "X · Posėdis · VU SA") and the page-type silhouette, never from per-workspace colours. Editing sits on the tinted `secondary` canvas with a "REDAGUOJI" eyebrow, so viewing never looks like editing. At most one ink band per screen, only with meaning.
- Design widths are 390 · 820 · 1180 · 1440, light + dark. Touch targets ≥44px via `pointer-coarse:`. Nothing hover-only. Dialogs become bottom sheets below `md`. Sticky save bar; filters go in a sheet on mobile.

## Legacy admin pages: migrate on touch, then fence
Pages still on `AdminContentPage`, `PageContent` or `FormUpsertLayout` (`UpsertModelLayout`) are legacy (`IndexTablePage` is gone). Don't build new pages on them. When you substantially change one, migrate it:
- classify it (page type) and place it (catalog entry)
- strip cards, shadows, `rounded-*`, raw hues and sub-12px text
- rebuild on the page-type shell with one primary action; move relations to the record and delete into ⋯
- fix copy (tu, glossary)
- add states: teaching empty, content-shaped skeleton, error, no permission
- check 390/820/1180/1440 light + dark, touch and keyboard
- test the changed contracts (gates, redirects, deferred props)
- retire stale tours and docs
Then add the path to `MIGRATED_ADMIN_PATHS` in `eslint.config.mjs`; the `admin-redesign/no-legacy-utility` error rule applies there. Code kept only for backward compatibility carries `@deprecated` naming its replacement. When no `Pages/Admin` file imports `ui/card` any more, turn that restriction from warn into error and fence all of `Pages/Admin/**`.

## Pradžia typography exception
The general uppercase button and sentence-case section-heading defaults do not apply to Pradžia. For ShowAdminHome.vue and Components/Home/**, follow the scoped Pradžia home hierarchy rule: sentence-case quick-action buttons and icon-led tracked uppercase section headings.

## Scroll regions and navigation scroll
The admin shell uses an inner scroll container (`div[data-slot="admin-scroll-area"]`) with the `scroll-region` attribute. Inertia resets scroll to top (`(0, 0)`) by default on all page visits (`<Link>`, `router.visit`).
- **Default off**: Never add `preserveScroll: true` to navigation links, tabs, breadcrumbs, or catalog items.
- **When `preserveScroll: true` is allowed**:
  1. Inline table mutations (restoring, toggling status, task completion).
  2. Submitting drawer/sheet forms (`SheetForm`) over a base collection or record.
  3. Granular sub-mutations on record pages (e.g. uploading an agenda document, generating a password).
  4. Saving an edit form in place (`Edit*.vue` updates) to maintain the editor's position.
