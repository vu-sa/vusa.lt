# Phases, PR map and open questions

> **Admin redesign.** Read when: you are picking up work or opening a PR.
> Index: [README.md](README.md) · Process: [process.md](process.md)

## PR map

One PR = one row. Rules:

- **One PR never mixes foundation with page migration.** Tokens, the catalog and the shell land
  before the pages that use them.
- Everything lands on `dev`; anything user-visible sits behind the opt-in flag until Phase 8.
- Each PR body states: the checklist item, the playbook steps done, the gate output, screenshots at
  390 · 820 · 1180 · 1440 (light + dark) where UI changed.
- Aim for a PR a session. If one grows past that, split it at the nearest row boundary.
- `→` = depends on.

| PR | Scope | → |
|---|---|---|
| **0.1** | This plan + the `AGENTS.md` pointer | — | ✅ |
| **0.2** | Fix recently-visited tracking (`route: "page"` for every entry) | — | ✅ |
| **0.3** | Admin route inventory → committed as a plan appendix ([inventory.md](inventory.md)) | — | ✅ |
| **0.4** | Browser-session continuity (U22): survive browser close, retain the Microsoft account picker, optional Microsoft federated logout | — |  ✅  |
| **0.5** | Rename `InstitutionAdministrator` → `InstitutionSecretary` everywhere (O22), incl. the table and lang keys | — | ✅ |
| **0.6** | Role label "Resursų administratorius" → "Išteklių administratorius" (data migration + seeder/test grep) | — | ✅ |
| **2.1** | Admin surface tokens: `[data-surface="admin"]`, radius 0, font, opt-in flag (pulled forward from 4.1) | — | ✅ |
| **2.2** | Colour system tokens: 6 status roles + 8 categories, Storybook + axe contrast, Storybook swatches | 2.1 | ✅ |
| **2.3** | `StatusBadge` + a label map per state enum (reservations, votes, tasks, content, support) | 2.2 | ✅ |
| **2.4** | Entity-type registry: icon + category colour, one source for every surface | 2.2 | ✅ |
| **2.5** | `EmptyState`, content-shaped skeletons, navigation progress bar (O16) | 2.1 | ✅ |
| **2.6** | Date/time formatter (U9) | — | ✅ |
| **2.7** | Picker set per [Pickers and inputs](rules/pages.md#pickers-and-inputs): native on coarse pointers, one per data kind | 2.1 |
| **2.8** | Primitive audit: input, select, table, tabs, sheet, dialog, calendar, command tokenised | 2.1 |
| **2.9** | Lint fence scaffolding: `MIGRATED_ADMIN_PATHS` (empty), raw-hue and `rounded`/`shadow` rules | — |
| **3.1** | PHP navigation catalog + cached Inertia prop + per-persona tests + route-coverage guard | — |
| **3.2** | Old shell reads the catalog (sidebar, Administravimas, palette, quick actions, ActionWindow) | 3.1 |
| **3.3** | Merge tools become record/bulk actions (O10) | 3.1 |
| **4.1** | Opt-in flag + shell skeleton: top bar, workspace picker (O25), section tabs | 3.1 |
| **4.2** | Mobile bottom bar + Meniu panel | 4.1 |
| **4.3** | Palette: catalog go-to, create, workspace ranking, Neseniai, pin star | 4.1 |
| **4.4** | Account menu, Pagalba (O13, U15), docked START FM, banners | 4.1 |
| **4.5** | Breadcrumbs below section level; tasks indicator → badges (O12) | 4.1 |
| **4.6** | Prefetch + instant visits (U2), keyboard set (U3) | 4.1 |
| **4.7** | 403 pages that explain themselves (U8) | 3.1 |
| **4.8** | Welcome tour (≤ 5 steps); retire sidebar-targeting tours and spotlights | 4.1 |
| **4.9** | Remove the density preference (U20) | 4.1 |
| **4.10** | Device split counter (U26) | — |
| **5.1** | **P1** Pradžia: attention queue, empty state, create shortcuts, Neseniai redaguota, koordinatorius | 4.x |
| **5.2** | **P2** Posėdžiai + extract `CollectionPage` (three views, filter bar, Rodyti daugiau, URL state) | 5.1 |
| **5.3** | **P3** Meeting record + agenda editor + extract `RecordPage` (Veikla, ‹ 3/24 ›, edit-mode canvas) | 5.2 |
| **5.4** | **P4** ActionWindow restyle (guided flow, bottom sheet, date presets) | 5.1 |
| — | *Feel review with reps — no PR; findings land as plan edits* | 5.4 |
| **5.5** | **P5** Duty record + form + Priskirti sheet (O21) + extract `FormPage` and `SheetForm` | 5.3 |
| **5.6** | **P6** Rezervacijos: table view, preview pane, bulk bar, optimistic approve (U4–U6) | 5.2 |
| **5.7** | **P7** Žymos: sheet form, trash filter + undo (O11), merge action | 5.5 |
| **5.8** | **P8** ViSAK overview + extract `OverviewPage` (numbers-as-links, one chart) | 5.1 |
| **5.9** | **P9** Paskyra → Mano rolės ir pareigybės (O14) | 4.7 |
| **5.10** | `/mano/search` reduced to cross-entity results | 5.2 |
| **6.1** | `BaseNotification`: `primaryAction()` + `context()`; in-app list renders the contract | — |
| **6.2** | Branded mail layout + digest template; retire the bespoke blades | 6.1 |
| **6.3** | Per-type pass 1: tasks + meetings (subjects, actions, context, channels) | 6.2 |
| **6.4** | Per-type pass 2: reservations, registrations, comments, the rest | 6.2 |
| **6.5** | Push payloads + quiet hours (rules 12–13); `NotificationCategory::color()` → `--cat-*` | 6.1, 2.2 |
| **7.1** | Rezervacijos overview | 5.8 |
| **7.2** | Svetainė overview (analytics compact) | 5.8 |
| **7.3** | Organizacija overview (new) | 5.8 |
| **7.4** | Sistema overview (new) | 5.8 |
| **7.5** | Visi skyriai at `/mano/administration` | 3.1 |
| **7.6** | First-login checklist (U13) | 5.1 |
| **7.7** | Access-change notice + history (U14) | 5.9 |
| **7.8** | Visible impact + koordinatorius on rep screens (U24, O22) | 5.1 |
| **7.9** | Outcome metrics report (U25) | — |
| **7.10** | Answerable reminder experiment on the periodicity gap (U21) | 6.3 |
| **8.1** | New shell becomes the default; opt-in + old shell + sidebar components removed | 7.x |
| **8.2** | Announcement, changelog LT + EN, user docs that describe navigation | 8.1 |
| **9.1** | Institution record + form (+ Prižiūri) | 8.1 |
| **9.2** | Reservations: create, record, resources, categories | 8.1 |
| **9.3** | Users record + form; duties collection; occupancy sheet everywhere; Kadencijos keitimas; timeline | 8.1 |
| **9.4** | Problems; forms and registrations | 8.1 |
| **9.5** | Website content: pages, news, calendar editors | 8.1 |
| **9.6** | Website content: banners, navigation builder, quick links, event types | 8.1 |
| **9.7** | Files, documents, Sharepoint | 8.1 |
| **9.8** | Sistema: roles, permissions, types, relationships, settings, status, mail queue, support | 8.1 |
| **9.9** | Paskyra: profile, notification settings; Užduotys; Pranešimai | 8.1 |
| **10.1** | Lint fence covers all of `Pages/Admin/**`; `ui/card` → error | 9.x |
| **10.2** | `record-rule` for the durable rules; `AGENTS.md` + component docs; fix the doc drift | 10.1 |
| **10.3** | Remove the redesign pointer, delete this file, re-run the evidence queries and rep metrics | 10.2 |

---

## Phase 0 — Decide and inventory

- [x] D1–D13 settled (2026-09-17)
- [x] O1–O17, O19–O24, O26 settled (2026-09-17); O18 glossary before the Phase 5 pilot copy
- [x] O18 glossary, O22 term (**sekretorius**), O25 picker, workspace names (**Organizacija**, **ViSAK**) settled (2026-09-17)
- [x] U1–U15, U17–U20, U22–U26 settled; U16 dropped; U21 is an experiment (2026-09-17)
- [x] Plan committed to `dev` with the `AGENTS.md` pointer, so every branch inherits it (2026-09-17)
- [x] Inventory: every `/mano` GET route → workspace, section, page type, default view, backend, gate,
      audience, reach. Resolves every `?` in the workspace map and the O1 defaults table. (See [inventory.md](inventory.md)) (2026-09-18)
- [x] **Baseline:** run the rep metrics (U25) and record them here (2026-09-17 snapshot below)
- [ ] Check production session settings and Microsoft login friction (U22) [Deferred by user instruction]
- [x] **Fix recently-visited tracking:** resolved catch-all `page` route collision with `fallback()` constraint, added client-side route name protection, and repaired existing DB records via migration `2026_09_18_100000_repair_recently_visited_admin_routes.php` (2026-09-18)
- [x] **Rename InstitutionAdministrator → InstitutionSecretary** everywhere (O22, PR 0.5): migration `2026_09_18_100100_rename_institution_administrators_to_secretaries.php`, model, morph map, relations, controller, form request, routes, translations, Vue components, and tests (2026-09-18)
- [x] **Role label "Resursų administratorius" → "Išteklių administratorius"** (PR 0.6): migration `2026_09_18_100200_rename_resource_manager_roles.php` (2026-09-18)
- [ ] Beta end date (D12)

### Rep Metrics Baseline (Snapshot 2026-09-17)

Measured on the production database clone (last meeting 2026-09-16):

| Metric | Baseline Value | Target |
|---|---|---|
| Meeting recorded within 7 days | **91.9%** (582 of 633 in past 12m) | > 95% |
| Agenda items with vote information | **35.1%** (1,582 of 4,510) | > 50% |
| Active rep duties active in last 30 days | **27.0%** (83 of 307) | > 50% |
| Task completion rate (overall) | **67.2%** | > 80% |
| Task completion: Meeting agenda | **67.8%** | > 80% |
| Task completion: Periodicity gap | **44.9%** | > 65% |
| Task completion: Reservations | **93.6%** | > 95% |

## Phase 1 — Second prototype round (v0) against the real structure

- [ ] Brief v0 (below); extract to `.design-reference/v0-admin/` (sources only)
- [ ] Review at the four widths, light + dark, with the four personas
- [ ] Fold findings back into *The feel*, *Rules* and the decisions

Brief:

> Redesign the internal system of VU SA (Vilnius University Students' Representation) in the visual
> language of the attached public site: warm paper / near-black canvas, VU SA red (light) / amber
> (dark) brand, square shapes, hairline rules, uppercase eyebrows, Atkinson Hyperlegible. It is an
> **operational tool**: denser than the public site, no photo heroes, uppercase only for eyebrows,
> workspace tabs and the primary button, minimum 12px text, status colours separate from brand.
> Navigation: a top bar with workspace tabs (Pradžia, Atstovavimas, Rezervacijos, Svetainė, Žmonės,
> Sistema — each user sees only the ones they hold permissions for), section tabs below, a
> field-shaped ⌘K command palette trigger, a **+ Sukurti** button, a bell, an account menu. Below
> 768px: a bottom bar (Pradžia · primary workspace · + · Užduotys · Meniu). Personas: (1) a student
> representative who records institution meetings and their agenda votes, 5 minutes a day, mostly on
> a phone; (2) the same person who is also a communications coordinator; (3) a super admin.
> Screens: Pradžia (attention queue from tasks — including the empty state; upcoming meetings,
> reservations); the Posėdžiai collection (quick filters, search, filter chips, rows on mobile);
> a meeting record; a focused agenda-item editor with "Punktas 3 / 12" previous/next and a notes side
> panel; a duty edit form with LT/EN fields where section intros sit above the fields and members are
> managed on the record page, not in the form. Statuses are tinted badges with an icon in six roles
> (neutral, info, progress, attention, success, danger); each entity type has a small icon + colour
> mark; editing happens on a tinted canvas so it never looks like viewing. Widths 390, 820, 1180,
> 1440; light and dark.

## Phase 2 — Foundation: the admin surface

- [x] `[data-surface="admin"]` token block (+ `.dark`), `resources/css/admin/surface.css` (PR 2.1,
      2026-09-18) — emitted per-user via the opt-in flag (see Notes), not only by the new shell;
      4.1 inherits the flag rather than inventing it
- [x] Colour system (O23): six status roles + eight categories as tokens, both themes, validated by
      Storybook + `@storybook/addon-a11y` (PR 2.2, 2026-09-18) — see Notes for why "validated by
      script" became Storybook
- [x] `StatusBadge` + canonical presentation maps for reservation resources, votes, student benefit,
      tasks, content and support requests (U10, PR 2.3, 2026-09-18)
- [x] **Entity-type registry (O24):** icon + category colour per entity type, shared by title bands,
      rows, palette, notifications and Veikla
- [ ] Operational type scale (deferred, see Notes); [x] `tabular-nums` for tables (PR 2.1)
- [x] Radius 0 under the admin surface (PR 2.1); audit of `rounded-full` survivors **not done** —
      358 occurrences / 185 files measured (see Notes), left for PR 2.8 and per-page migration
- [x] Font follows the surface (D10) — PR 2.1
- [x] Touch-target utility on `pointer-coarse:` — `u-touch` in `admin/surface.css` (PR 2.1), unused
      until PR 4.1 becomes its first consumer
- [ ] Primitive audit for hardcoded `bg-white`/`zinc-*` the public work did not need (input, select,
      table, tabs, sheet, dialog, calendar, command)
- [x] `EmptyState` (U11), content-shaped skeletons, navigation progress bar (O16) — PR 2.5 (2026-09-19)
- [x] Date formatter (U9) — PR 2.6 (2026-09-19); the picker set per [Pickers and inputs](rules/pages.md#pickers-and-inputs) (U18, PR 2.7)
- [x] Storybook: admin surface, status-role and category swatches; a11y `error` on new admin patterns
      (U17) — `Patterns/AdminSurface.stories.ts`, `Patterns/ColourSystem.stories.ts` (PR 2.1 + 2.2)
- [ ] Lint fence scaffolding: a `MIGRATED_ADMIN_PATHS` glob list, empty at first

### PR 2.1 + 2.2 Notes (2026-09-18)

- **Decision — opt-in flag pulled forward from PR 4.1.** `ui_preferences.appearance.new_shell`
  (`HasUIPreferences::getNewAdminShellEnabled()`/`setNewAdminShellEnabled()`), resolved by the new
  `App\Support\DesignSurface::for()` and read by `app.blade.php`. A plain ghost-button toggle
  ("Naujas dizainas (beta)", no spotlight, no dialog) sits in `AdminLayout`'s header; PR 4.1
  inherits the flag, PR 4.4 replaces the button with an account-menu entry. Nothing here reaches
  staging before the whole redesign is done, so no announcement/changelog/spotlight.
- **Decision — no contrast script.** `.design-reference/tokens.mjs` (the public revamp's pattern)
  is untracked and asserts nothing — it only prints resolved colours for a human to read; no repo
  script computes a contrast ratio anywhere. Validation is instead two Storybook stories
  (`Patterns/ColourSystem.stories.ts`, `a11y: { test: 'error' }`) rendering real text on real
  surfaces, so axe's `color-contrast` rule asserts the 4.5:1 requirement in a real browser, in
  every Surface × Theme combination. `resources/js/__tests__/designTokens.test.ts` guards the
  token *list* (completeness, var()-indirection) since axe can't see that. Known gaps: axe checks
  text contrast only (the "no two categorical hues < 25° apart" rule is asserted by the Vitest
  guard, not axe), and CI's `storybook-tests` job is `continue-on-error: true`, so this gates the
  local per-phase gate, not CI yet.
- **Decision — type scale deferred (D7).** Admin's root `font-size` resets from admin.css's 90% to
  100% under the surface (the floor rules/visual.md's 12px rule needs), but no operational
  `--text-*` scale ships yet — Tailwind's defaults apply. Revisit once real pages show whether
  100% reads too loose.
- **Decision — `--color-status-*` replaced in place**, no legacy aliases. The old literal block
  (success/warning/danger/info/neutral, inside `@theme inline` so it could never be re-scoped) had
  exactly 9 consumers, all in `Components/Public/Search/**`; all 9 migrated in PR 2.2
  (`warning` → `danger` for a vote mismatch, `warning` → `attention` for a mixed alignment dot).
- **Not done, left for later phases:** `rounded-full` audit (358 / 185 files, PR 2.8 + per-page);
  the operational type scale (D7, deferred above); `driver-tour.css` (277 lines of hardcoded hex
  with `!important` — does not follow any surface; retired with the tours in PR 4.8, not migrated);
  `NotificationCategory::color()` remap onto `--cat-*` (flagged with a `@todo` pointing at PR 6.5,
  left alone rather than "fixed" into the status vocabulary it collides with).
- **Storybook decorator fix, not scoped to a story file:** `.storybook/preview.ts`'s
  `withSurfaceAndTheme` decorator previously treated the toolbar's `admin` Surface value as "no
  scope" (it only special-cased `public`) — existing admin stories pinning
  `globals: { surface: 'admin' }` (`MeetingForm`, `AgendaItemsForm`, `NavigationBuilder`) were
  silently rendering on the *legacy* palette. Now `admin` stamps `data-surface="admin"`, same as
  `public` stamps `data-surface="public"`; all three existing pins were re-verified green.

### PR 2.3 + 2.4 Notes (2026-09-18)

- `StatusBadge` accepts a canonical `StatusPresentation` rather than separate label, role and icon
  props. The maps in `Constants/statuses.ts` therefore keep all three parts of U10 together and
  make colour-only or caller-specific variants harder to introduce accidentally.
- `Constants/entityTypes.ts` covers every generated `ModelEnum` value and is now the source behind
  the existing `getModelIcon()` compatibility helper as well as `EntityTypeMark`. The eight anchor
  assignments remain Posėdis · Rezervacija · Narys · Pareigybė · Naujiena · Renginys · Dokumentas ·
  Institucija; related models reuse the nearest category.
- Both components have admin-surface Storybook stories with a11y set to `error`; registry and
  state-map completeness are guarded by Vitest so newly generated enum values cannot silently ship
  without presentation metadata.

### PR 2.5 + 2.6 Notes (2026-09-19)

- **`EmptyState` (U11, O16):** Moved directly to `resources/js/Components/Patterns/EmptyState.vue` as
  the single canonical component (with `Components/Empty/EmptyState.vue` delegating to it for
  backwards compatibility). Supports two distinct modes:
  - `empty` (default): first-use teaching state with mascot/custom icon, title, description, primary
    action button, and docs link.
  - `no-results`: search/filter empty state with `SearchX` icon, localized heading and description,
    and clear filters action.
  - Square hairline chip (`size-12 border border-border bg-secondary text-muted-foreground`)
    replaces the legacy `rounded-full` chip.
- **Content-shaped skeletons (O16, O3):** Reusable skeletons under
  `resources/js/Components/Patterns/Skeletons/`:
  - `CollectionSkeleton`: supports both `viewMode="rows"` (hairline row list with icon/date plate,
    title, badges) and `viewMode="table"` (table header + tabular rows with varying column widths and
    `sr-only` labels for axe compliance).
  - `RecordSkeleton`: title band (icon plate, title, status, primary action button), key facts strip
    (4 fact tiles), and card content block.
  - `FormSkeleton`: question heading, intro text, single-column field measure (~40rem) with labels,
    input boxes, hints, and sticky save bar placeholder.
  - `SectionCardSkeleton`: card header with title and count badge, and hairline item rows.
  - Uses admin semantic tokens (`bg-secondary`, `bg-secondary/70`, `border-border`, `bg-card`) with
    zero radius and reduced-motion support.
- **Navigation progress bar (O16, O3):**
  - Inertia global progress bar `#nprogress .bar` tokenised in `resources/css/admin/surface.css` at 2px
    height with brand colour (`var(--brand-fill, var(--brand))`); `#nprogress .peg` blurry box-shadow
    removed per rule 5.
  - Reusable hairline `TopProgressBar.vue` component in `Patterns/` (accessible progressbar with
    `aria-label`, indeterminate scanning bar or determinate percentage). Mounted in
    `ServerDataTable.vue` when `loading` is true, replacing empty lags with a thin top progress bar.
- **Date/time formatter (U9):**
  - `resources/js/Utils/dateTime.ts` is the single canonical formatter locked to `Europe/Vilnius`
    timezone and Lithuanian formats by default.
  - `formatDate`: default `YYYY-MM-DD` ('iso') for tables, 'full' ("2026 m. rugsėjo 19 d.") for record
    facts, and 'short'.
  - `formatTime`: 24-hour time `HH:mm`.
  - `formatDateTime`: combined date and time.
  - `formatNearDate`: relative when near ("prieš 2 val.", "po 3 d.", "vakar", "rytoj", "ką tik"),
    falling back to absolute date beyond 7 days.
  - `formatDateFact`: structured `{ display, tooltip, isNear, relative, absolute }` for record facts
    strips.
  - `useDateFormatter()` composable in `resources/js/Composables/useDateFormatter.ts` binding reactive
    Inertia locale. Re-exported via `Utils/IntlTime.ts`.
- **Validation:** Storybook stories for `EmptyState`, `Skeletons`, and `TopProgressBar` on
  `surface: 'admin'` with axe a11y set to error (`test:storybook`); 100% passing across all 17 story
  test files (90 tests) and 394 Vitest test files (3,004 tests). Clean ESLint check across all
  modified/new files.

## Phase 3 — Navigation catalog

- [ ] PHP catalog: workspaces → sections → create actions, gated by `viewAny` / `create` (O19)
- [ ] Shared as a cached, permission-invalidated Inertia prop
- [ ] Feature tests: the four personas see exactly their workspaces and sections
- [ ] Guard test: every `/mano` index route is in the catalog or the exclusion list
- [ ] The **old** shell reads it too (`AppSidebar`, `ShowAdministration` → Visi skyriai, palette,
      quick actions, ActionWindow, mobile bars) — drift ends before the new shell exists
- [ ] Merge tools become actions (O10)

## Phase 4 — The shell (behind the opt-in)

- [x] Opt-in flag in `ui_preferences` — pulled forward into PR 2.1 (2026-09-18) so the surface
      tokens could be inspected as they landed. `appearance.new_shell` already exists; this phase
      only needs to move "Naujas dizainas (beta)" from AdminLayout's plain header button into the
      account menu proper, alongside Paskyra/Išvaizda/Pagalba below
- [ ] Top bar, workspace tabs + Daugiau, section tabs, field-shaped palette trigger, **+ Sukurti**, bell, account
- [ ] Mobile bottom bar (all mobile) + Meniu accordion
- [ ] Palette: catalog go-to, create, workspace ranking, Neseniai, pin star (D5, O20)
- [ ] Breadcrumbs only below section level
- [ ] Account menu: Paskyra, Išvaizda (+ `AccessibilityMenu`), Pagalba (O13, U15), START FM (O15), Apie, Atsijungti
- [ ] Banners (O15); tasks indicator removed, badges instead (O12)
- [ ] Prefetch/instant visits on tabs (U2); keyboard set (U3); 403 explanations (U8)
- [ ] Welcome tour (≤ 5 steps); retire sidebar-targeting tours and spotlights
- [ ] Performance check on a throttled phone (U19); coarse device split (U26)
- [ ] Remove the density preference (U20); stay-logged-in fixes (U22)
- [ ] Staging dogfood (internal); the public beta opens at the end of Phase 7

## Phase 5 — Pilot slice

Build the page-type layouts **through real pages**, not in the abstract. Every concept is exercised
once; a page type is extracted into a shared layout only after a pilot page proves it.

| # | Pilot | Page type | Proves |
|---|---|---|---|
| P1 | **Shell + Pradžia** | Overview | workspaces from permissions, catalog, attention list + empty state, **+ Sukurti**, palette, bottom bar + Meniu, account menu, START FM, Neseniai redaguota, Tavo koordinatorius (O22), identity and entity markers (O24) |
| P2 | **Posėdžiai** | Collection (Typesense) | `ShowDocuments` anatomy, three views, quick filters, unit as a filter, Rodyti daugiau + restore, URL state, mobile rows |
| P3 | **Meeting record + agenda-item editor** | Record + focused editor | canonical record, key facts, Veikla, ⋯ actions, sequential editing, local side panel, autosave, Papildyk list (R-c), votes as taps (R-e), edit-mode canvas (O24) |
| P4 | **ActionWindow: naujas posėdis** | Guided flow | flow restyle, bottom sheet, date presets |
| P5 | **Duty record + duty form + Priskirti sheet** | Record + Form + Sheet | occupancy sheet (O21), associations out of the form, reorder mode, text placement, LT/EN switch, Papildomi nustatymai, pickers, save bar, delete in ⋯ |
| P6 | **Rezervacijos** | Collection (database) | table default, preview on a queue, ‹ 3 / 24 ›, bulk selection bar, optimistic approve, status roles |
| P7 | **Žymos** | Collection + sheet form | sheet form, trash filter + undo toast, merge as action |
| P8 | **ViSAK overview** | Overview | section tabs, numbers-as-links, one chart with a text summary, categorical colours |
| P9 | **Paskyra → Mano rolės ir pareigybės** | Account | O14, the target of every 403 explanation, access-change history (U14) |

- [ ] **Wave A — the feel** (a rep's day on a phone): P1 → P2 → P3 → P4
- [ ] Each pilot page adds its paths to `MIGRATED_ADMIN_PATHS` when it lands
- [ ] **Feel review** — live sessions with 3–5 reps on their own phones ("užfiksuok vakarykštį
      posėdį") and one coordinator; update *The feel* and *Rules*
- [ ] **Wave B — the admin concepts:** P5 → P6 → P7 → P8 → P9
- [ ] **Concept review;** extract the proven layouts: Overview, Collection, Record, Form, Sheet form,
      Workbench shell; `/mano/search` reduced to cross-entity results (O1)
- [ ] Update the [Migration playbook](playbook.md#migration-playbook) with what the pilot taught

## Phase 6 — Messages (email, push, in-app)

See [Messages](messages.md#messages--email-push-and-in-app). Done before the beta opens, because the beta's
reps will meet the app through these.

- [ ] Extend `BaseNotification` with `primaryAction()` and `context()`; one content contract, three
      renderings (rule 1)
- [ ] Brand the mail layout (`resources/views/vendor/mail/html/*`): square, hairlines, red button,
      wordmark, ~600px, light-only
- [ ] Retire the bespoke blades that exist only because the layout was ugly (`comment-posted`,
      `assigned-to-resource`, `feedback`, the registration pairs, `reminder-to-login`) — keep only what
      genuinely differs
- [ ] Digest template on the same structure (grouped by category; title + context + link per item)
- [ ] Per-type pass over the 24 notifications with the channel-policy table: subject, primary action,
      context rows, channel, signature (rules 2–9)
- [ ] "Why you got this" footer + link to Pranešimų nustatymai; plain-text alternative checked
- [ ] Push payloads follow the same contract; quiet hours 22:00–07:00 for non-urgent (rule 13)
- [ ] Remap `NotificationCategory::color()` onto `--cat-*`
- [ ] In-app notification list renders the shared contract (O12: notifications = what happened)
- [ ] Screenshots of every template from Mailpit at 360px and desktop, both locales

## Phase 7 — Remaining overviews and the rep loop

- [ ] Rezervacijos ← `ShowReservations`
- [ ] Svetainė ← `ShowSvetaine` (analytics compact, O17)
- [ ] Organizacija (new) · Sistema (new)
- [ ] Visi skyriai at `/mano/administration`
- [ ] First-login checklist (U13); access-change notice (U14)
- [ ] Visible impact (U24); outcome metrics report (U25); device split (U26)
- [ ] **Experiment:** answerable reminders on the periodicity-gap reminder; compare that task type's
      completion rate with the Phase 0 baseline (U21)
- [ ] **Open beta** (2–4 weeks, end date announced)

## Phase 8 — Switch

The beta ends on its date; the long tail continues **inside** the new shell.

- [ ] Beta feedback folded in; go / no-go
- [ ] New shell becomes the default; opt-in and old shell removed
- [ ] Delete `AppSidebar`, `Sidebar/*`, `NavMain`/`NavSecondary`, `SidebarStartFM` (its player moves
      into the docked START FM), the customise dialog, the density variants in `ui/sidebar`, and
      `ui/sidebar` itself if unused (re-grep first)
- [ ] Welcome tour for everyone; system announcement; changelog LT + EN (⭐ new admin)
- [ ] User docs (`docs/`, `docs/en/`) that describe navigation

## Phase 9 — The long tail, by reach

Each page follows the playbook and lands with its lint-fence paths; tick here.

- [ ] Institution record + form (+ Prižiūri, O22)
- [ ] Reservations: create, record; resources; categories
- [ ] Users (record + form), duties collection, the occupancy sheet everywhere (O21), *Kadencijos
      keitimas* wizard, duty timeline (workbench)
- [ ] Problems
- [ ] Forms and registrations
- [ ] Website content: pages, news, calendar (editors), banners, navigation (workbench), quick links,
      event types
- [ ] Files, documents, Sharepoint
- [ ] Sistema: roles, permissions, types, relationships, settings, system status, mail queue, support
- [ ] Paskyra: profile, notification settings; Užduotys; Pranešimai

## Phase 10 — Fence and clean-up

- [ ] Lint fence covers all of `resources/js/Pages/Admin/**`; `ui/card` under `Pages/Admin/**` → error
- [ ] `record-rule`: the feel + core concepts, page types, form rules, pickers, colour system,
      distinctness
- [ ] `AGENTS.md` + `Components/CLAUDE.md` + `Tables/CLAUDE.md`; fix drift found while investigating:
      `AGENTS.md` links a missing `resources/js/Composables/BREADCRUMBS_GUIDE.md`; check whether
      "admin searches must use the database Scout driver" still holds
- [ ] Remove the redesign pointer from `AGENTS.md`; delete this file once its rules live in `.ai/rules`
- [ ] Re-run the evidence queries and the rep metrics; compare with the Phase 0 baseline

---

## Open questions for humans

- [ ] Beta end date; who dogfoods on staging (Wave A feel review participants)
- [ ] How users hear the sidebar is going away (email, system announcement, changelog)

---

## Scope, gaps and trade-offs

Summary of the 2026-09-17 thinking session, for whoever starts implementing.

### In scope

- **Structure:** workspaces from permissions, one server-side navigation catalog, top bar + mobile
  bottom bar, palette, one create entry, Visi skyriai.
- **Page system:** seven page types; rules for collections, records, forms and pickers; the
  migration playbook.
- **Visual system:** admin tokens on the public language, type scale, colour system, wayfinding and
  distinctness, touch and responsive rules.
- **Behaviour:** URL state, prefetch, optimistic updates, Precognition, undo, 403 explanations,
  onboarding and help reset.
- **Reps:** the obligation loop (record now / complete later, answerable reminders, staying logged
  in, a named coordinator, visible impact) and outcome metrics.
- **Messages:** email, push and in-app — one content contract, a channel policy per type, a branded
  mail layout.
- **Delivery:** evidence, rollout (staging → time-boxed beta → switch), pilot waves, phases, working
  across sessions and models.

### Deferred — in the plan, decided later

- **English-language admin users** — every copy decision assumed Lithuanian.
- **Other specialist roles in depth** — PKP administrators, registration data recipients and archive
  managers appear only as permission sets.
- **Workbench internals** — rich content editor, navigation builder, Gantt and institution graph are
  reskinned, not rethought.
- **Backend work behind rep features** — actionable reminders, the occupancy sheet, coordinator
  resolution and metrics need controller/notification changes not yet designed.
- **Baselines** — no accessibility audit, performance numbers or device split exist yet.
- **Effort and capacity** — no estimate, no owner per phase.
- **Change management** — telling ~300 reps the interface changed; rewriting `docs/`.
- **Public-site colours** — the colour system is written for both surfaces but designed against the admin.

### Advantages

- The interface is exactly as complex as a person's permissions — a rep sees two or three tabs.
- One catalog ends navigation drift and is testable per persona.
- One brand language across the public site and the admin.
- The same navigation model on phone, tablet and desktop.
- Duplicates disappear: search vs index pages, create/edit page pairs, five navigation lists, five
  duty-assignment screens.
- Page types + playbook make migration incremental — possible for any contributor or model.
- Rep work is judged by outcomes (recording speed, completeness), not by looks.

### Disadvantages and risks

- **Muscle memory:** returning users lose the sidebar; tours, spotlights and docs go stale at once.
- **Few top-level slots:** people with five or six workspaces lean on Daugiau, the palette and Visi skyriai.
- **Sameness and affordance:** square, hairline, uppercase styling can flatten pages and make controls
  look less clickable — hence [Wayfinding and distinctness](rules/visual.md#wayfinding-and-distinctness).
- **Mixed look:** restyled and legacy pages coexist through the beta and the long tail.
- **The collection component** (two backends, three views, selection, trash) can grow into an
  over-general monster — build it only through pilots P2, P6 and P7.
- **Typesense lag** after edits needs deliberate handling.
- **Capacity:** ~130 admin GET routes for a student team; several rep features need backend work
  beyond a redesign.
- **Consistency across people and models** drifts unless rules are enforced by lint, stories and
  tests rather than prose.

### Before implementation starts

1. Settle O21–O24 (duty assignment, coordinator, colour system, distinctness). O18 can wait for the
   pilot's copy.
2. Decide where this plan lives so every tool can read it (see [Working across sessions and models](process.md#working-across-sessions-and-models)).
3. Phase 0: inventory, the recently-visited tracking fix, production session settings.
4. **Snapshot the rep metrics now** — the baseline the redesign is judged against.
5. Branching: small PRs to `dev` behind the opt-in flag, not a long-lived branch.
6. Phase 1: the v0 round with the updated brief.
