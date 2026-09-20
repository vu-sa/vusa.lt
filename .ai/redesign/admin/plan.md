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

| PR | Scope | → | Status |
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
| **2.7** | Picker set per [Pickers and inputs](rules/pages.md#pickers-and-inputs): native on coarse pointers, one per data kind | 2.1 | ✅ |
| **2.8** | Primitive audit: input, select, table, tabs, sheet, dialog, calendar, command tokenised | 2.1 | ✅ |
| **2.9** | Lint fence scaffolding: `MIGRATED_ADMIN_PATHS` (empty), raw-hue and `rounded`/`shadow` rules | — | ✅ |
| **3.1** | PHP navigation catalog + cached Inertia prop + per-persona tests + route-coverage guard | — | ✅ |
| **3.2** | Old shell reads the catalog (sidebar, Administravimas, palette, quick actions, ActionWindow) | 3.1 | ✅ |
| **3.3** | Merge tools become record/bulk actions (O10) | 3.1 | ✅ |
| **4.1** | Opt-in flag + shell skeleton: top bar, workspace picker (O25), section tabs | 3.1 | ✅ |
| **4.2** | Mobile bottom bar + Meniu panel | 4.1 | ✅ |
| **4.3** | Palette: catalog go-to, create, workspace ranking, Neseniai, pin star | 4.1 | ✅ |
| **4.4** | Account menu, Pagalba (O13, U15), docked START FM, banners | 4.1 | ✅ |
| **4.5** | Breadcrumbs below section level; tasks indicator → badges (O12) | 4.1 | ✅ |
| **4.6** | Prefetch + instant visits (U2), keyboard set (U3) | 4.1 | ✅ |
| **4.7** | 403 pages that explain themselves (U8) | 3.1 | ✅ |
| **4.8** | Welcome tour (≤ 5 steps); retire sidebar-targeting tours and spotlights | 4.1 | ✅ |
| **4.9** | Remove the density preference (U20) | 4.1 | ✅ |
| **4.10** | Device split counter (U26) | — | ✅ |
| **5.1** | **P1** Pradžia: attention queue, empty state, create shortcuts, Neseniai redaguota, koordinatorius | 4.x | ✅ |
| **5.2** | **P2** Posėdžiai + extract `CollectionPage` (three views, filter bar, Rodyti daugiau, URL state) | 5.1 | ✅ |
| **5.3** | **P3** Meeting record + agenda editor + extract `RecordPage` (Veikla, ‹ 3/24 ›, edit-mode canvas) | 5.2 | ✅ |
| **5.4** | **P4** ActionWindow restyle (guided flow, bottom sheet, date presets) | 5.1 | ✅ |
| — | *Feel review with reps — no PR; findings land as plan edits* | 5.4 |
| **5.5** | **P5** Duty record + form + Priskirti sheet (O21) + extract `FormPage` and `SheetForm` | 5.3 | ✅ |
| **5.6** | **P6** Rezervacijos: table view, preview pane, bulk bar, optimistic approve (U4–U6) | 5.2 | ✅ |
| **5.7** | **P7** Žymos: sheet form, trash filter + undo (O11), merge action | 5.5 | ✅ |
| **5.8** | **P8** ViSAK overview + extract `OverviewPage` (numbers-as-links, one chart) | 5.1 | ✅ |
| **5.9** | **P9** Paskyra → Mano rolės ir pareigybės (O14) | 4.7 | ✅ |
| **5.10** | `/mano/search` reduced to cross-entity results | 5.2 | ✅ |
| **6.1** | `BaseNotification`: `primaryAction()` + `context()`; in-app list renders the contract | — | ✅ |
| **6.2** | Branded mail layout + digest template; retire the bespoke blades | 6.1 | ✅ |
| **6.3** | Per-type pass 1: tasks + meetings (subjects, actions, context, channels) | 6.2 | ✅ |
| **6.4** | Per-type pass 2: reservations, registrations, comments, the rest | 6.2 | ✅ |
| **6.5** | Push payloads + quiet hours (rules 12–13); `NotificationCategory::color()` → `--cat-*` | 6.1, 2.2 | ✅ |
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
- [x] Primitive audit for hardcoded `bg-white`/`zinc-*` the public work did not need (input, select,
      table, tabs, sheet, dialog, calendar, command) (PR 2.8, 2026-09-19)
- [x] `EmptyState` (U11), content-shaped skeletons, navigation progress bar (O16) — PR 2.5 (2026-09-19)
- [x] Date formatter (U9) — PR 2.6 (2026-09-19); picker set per [Pickers and inputs](rules/pages.md#pickers-and-inputs) (U18, PR 2.7, 2026-09-19)
- [x] Storybook: admin surface, status-role and category swatches; a11y `error` on new admin patterns
      (U17) — `Patterns/AdminSurface.stories.ts`, `Patterns/ColourSystem.stories.ts` (PR 2.1 + 2.2)
- [x] Lint fence scaffolding: a `MIGRATED_ADMIN_PATHS` glob list, empty at first (PR 2.9, 2026-09-19)

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

### PR 2.7 + 2.8 + 2.9 Notes (2026-09-19)

- **Pickers (U18):** the existing `DatePicker`, `TimePicker`, `DateTimePicker`, and occupancy
  `DateRangePicker` exports remain the single API for their data kinds. Fine pointers now type ISO
  dates and `HH:mm` values with calendar/time assistance; coarse pointers use native date/time
  controls. `DateTimePicker` is two fields rather than a combined popover, while preserving its
  `Date | null` caller contract. The date picker keeps its noon-UTC conversion to prevent date
  shifts in JSON payloads.
- **Primitive audit:** input, select, table, tabs, sheet, dialog, calendar/range-calendar and
  command now use semantic surface tokens and contain no raw hue, rounded or shadow utilities.
  The broader page-level `rounded-full` inventory remains a per-page migration concern.
- **Lint fence:** `eslint.config.mjs` exports an empty `MIGRATED_ADMIN_PATHS` list. Paths added by a
  future pilot receive an error for literal raw hues, rounded utilities (except `rounded-none`) and
  shadows (except `shadow-none`); legacy paths remain unaffected until enrolled.
- **Validation:** picker component tests, frontend build, all Storybook tests, and all 395 Vitest test files passed (100% clean). Fixed TopProgressBar boolean default props and restored Input component name so all component tests and stubs pass cleanly.

## Phase 3 — Navigation catalog

- [x] PHP catalog: workspaces → sections → create actions, gated by `viewAny` / `create` (O19) —
      `App\Services\AdminNavigation\{AdminNavigationCatalog,Workspace,Section,CreateAction,Visibility}`
      (PR 3.1, 2026-09-19)
- [x] Shared as a cached, permission-invalidated Inertia prop — `adminNavigation`, null outside
      `/mano`, invalidated by the existing `PermissionMapBuilder::forgetCachedMaps()` call sites
      (PR 3.1)
- [x] Feature tests: five personas see exactly their workspaces and sections (`toEqual` on the
      ordered key lists) plus an access-parity pass (every catalog-visible section actually opens,
      not 403) — `tests/Feature/System/AdminNavigationCatalogTest.php` (PR 3.1)
- [x] Guard test: every `/mano` index (or bare landing) route is in the catalog or the exclusion
      list, and every catalog route name is real (PR 3.1)
- [x] The **old** shell reads it too (`AppSidebar`, `useQuickActions`, `useActionWindowCatalog`,
      `adminPageCatalog.ts`, palette, mobile bars) — drift ends before the new shell exists.
      `ShowAdministration` already reads the catalog (PR 3.1); the rest is PR 3.2 (2026-09-19)
- [x] Merge tools become actions (O10) — PR 3.3 (2026-09-19)

### PR 3.2 + 3.3 notes (2026-09-19)

- **Old shell consumers migrated onto the catalog (PR 3.2):**
  - `AppSidebar`, `useCommandActions`, `useQuickActions`, and `useActionWindowCatalog` now all read
    `adminNavigation` via `useAdminNavigation` rather than hand-evaluating `auth.can` maps.
  - `auth.registrationForms` was retired from `HandleInertiaRequests` — intake forms now arrive
    directly through `AdminNavigationCatalog::registrationSections()`.
  - `useCommandActions` shrank from ~340 lines to 85 lines, dynamically populating navigation and
    create items from `adminNavigation.workspaces`.
  - Old shell components (`AppSidebar.vue`, `NavMain.vue`, `NavSecondary.vue`, `Sidebar/*`) and
    legacy `adminPageCatalog.ts` tagged with `@deprecated` ahead of removal in Phase 8.
- **Merge tools converted to bulk/record actions (PR 3.3, O10):**
  - Deleted obsolete full merge pages (`MergeUser.vue`, `MergeDuty.vue`, `MergeStudyPrograms.vue`,
    `MergeTags.vue`) and legacy GET routes (`tags.merge`, `users.merge`, etc. now redirect to index with an info flash).
  - Introduced `CollectionAction` onto catalog `Section`, gating collection-level actions via policies.
  - Built reusable `MergeRecordsDialog.vue` supporting both selected rows and search candidates.
  - Integrated merge action into index tables (`IndexTag`, `IndexDuty`, `IndexStudyProgram`, `IndexUser`)
    with selection mode and row-action triggers.
  - Created `MergeCandidateApiController` for debounced candidate searches across mergeable models.
- **Review and bug fixes:**
  - Updated `AdminNavigationCatalogTest` to assert the added `'collectionActions' => []` on sections.
  - Updated `FormAccessTest` to assert registration forms in `adminNavigation` sections rather than
    the retired `auth.registrationForms` prop.
  - Updated `ActionWindow.component.test.ts` to mock `adminNavigation` actions.
  - Fixed PHPStan type covariance and exhaustive match checks in `MergeCandidateApiController.php`.
  - Fixed duplicate docblock in `UserController.php` and unnecessary nullsafe in `AuthController.php`.
  - Tagged legacy compatibility code across phases 0–3 with `@deprecated`.

### PR 3.1 notes (2026-09-19)

- **Six drifted sources, not five.** O19 named `AppSidebar`, `ShowAdministration`,
  `useCommandActions`, `useQuickActions`, `useActionWindowCatalog`. A sixth,
  `resources/js/Composables/adminPageCatalog.ts` (405 lines, feeds the visit tracker and
  Neseniai), has the same shape of problem and is now also PR 3.2's job.
- **Found and fixed in passing:** `useCommandActions.ts` gated every navigation entry on
  `auth.can.read?.*`, a map the backend has never shared (it shares `auth.can.index`). Every
  navigation entry gated on it (Posėdžiai, Institucijos, Naudotojai, Pareigybės, Užduotys,
  Rezervacijos, Kalendorius, Naujienos) has never appeared in ⌘K, for anyone. Fixed to
  `auth.can.index` with a regression test.
- **Two real authorization surprises the access-parity test surfaced** (kept as-is — the catalog
  reflects the actual policy, it does not invent a narrower one):
  - `MeetingPolicy` has no `viewAny()` override, so a plain "Student Representative" (who only
    holds `meetings.read.own`) cannot see Posėdžiai, the ViSAK overview, or Darbotvarkės
    klausimai — despite being able to create and edit their own meetings.
  - `Išteklių administratorius` (Resource Manager) holds `tasks.read.padalinys` as part of its
    reservation-adjacent permission set, so it incidentally sees ViSAK's Užduočių suvestinė
    despite holding none of ViSAK's other permissions.
  - `ResourcePolicy::viewAny()` and `ReservationPolicy::create()` are both unconditionally `true`
    ("anyone can view the resource listing" / request a reservation), so even a plain member with
    no role sees Rezervacijos → Ištekliai and can reach **+ Sukurti** → Nauja rezervacija.
- **`ShowAdministration` migrated onto the catalog in this PR** (not deferred to 3.2 or 7.5),
  since leaving it on `auth.can.create.*` while every other consumer still drifted seemed a worse
  midpoint. It now gates on `viewAny` (was `create`) — a correction per the settled rule, shipped
  outside the opt-in flag, so it earned a ✨ changelog line. Its markup, search box and gradient
  tool tiles are unchanged; only the data source moved. The "Įrankiai" section is limited to
  Organizacija's two create actions (`duty_update`, `duty_periods`) to match the page's previous
  scope — the other workspaces' create actions (new_meeting, new_reservation, …) already have a
  home in the sidebar quick actions and ActionWindow, and get their catalog-backed consumer in 3.2.
  The per-item "jump to search tab" shortcut button is dropped — the catalog carries no
  `searchTab` metadata, and inventing one would be a seventh source of truth. PR 7.5 (the proper
  Visi skyriai redesign) or O1's search reduction (5.10) can reintroduce it once the catalog itself
  carries that signal.
- **Registracijos is the one dynamic section pair.** The member/student-rep registration forms'
  ids come from `FormSettings`, and visibility is a per-record `FormPolicy::view()` check, not a
  class-level `viewAny` — `AdminNavigationCatalog::registrationSections()` mirrors
  `HandleInertiaRequests::getViewableRegistrationForms()`; PR 3.2 retires the latter once
  `AppSidebar` reads the catalog instead of `auth.registrationForms`.
- **Not required by the coverage guard, on purpose:** `search.agendaItems` / `search.meetings` /
  `search.institutions` / `search.resources` are legacy redirects to `search.index` with a `tab`
  param (`SearchController`'s own docblocks say so) — the catalog's Darbotvarkės klausimai section
  links straight to `search.index?tab=agenda-items` rather than through the redirect.

## Phase 4 — The shell (behind the opt-in)

- [x] Opt-in flag in `ui_preferences` — pulled forward into PR 2.1 (2026-09-18) so the surface
      tokens could be inspected as they landed. `appearance.new_shell` already exists; this phase
      only needs to move "Naujas dizainas (beta)" from AdminLayout's plain header button into the
      account menu proper, alongside Paskyra/Išvaizda/Pagalba below
- [x] Top bar, workspace picker (O25), section tabs, field-shaped palette trigger, **+ Sukurti**, bell, account stub (PR 4.1, 2026-09-19)
- [x] Mobile bottom bar (all mobile) + Meniu accordion (PR 4.2, 2026-09-19)
- [x] Palette: catalog go-to, create, workspace ranking, Neseniai, pin star (D5, O20) (PR 4.3, 2026-09-19)
- [x] Breadcrumbs only below section level (PR 4.5, 2026-09-19)
- [x] Account menu: Paskyra, Išvaizda (+ `AccessibilityMenu`), Pagalba (O13, U15), START FM (O15), Apie, Atsijungti — PR 4.4 (2026-09-19)
- [x] Banners (O15) — PR 4.4 (2026-09-19)
- [x] Tasks indicator removed, badges instead (O12) (PR 4.5, 2026-09-19)
- [x] Prefetch/instant visits on tabs (U2); keyboard set (U3) (PR 4.6, 2026-09-19)
- [x] 403 explanations (U8, PR 4.7, 2026-09-19)
- [x] Welcome tour (≤ 5 steps); retire sidebar-targeting tours and spotlights (PR 4.8, 2026-09-19)
- [x] Coarse device split counter (U26, PR 4.10, 2026-09-19); performance check on throttled phone (U19) slated for Phase 5 Wave A rep feel review
- [x] Remove the density preference (U20, PR 4.9, 2026-09-19); stay-logged-in fixes (U22, PR 0.4; prod settings check deferred)
- [x] Phase 4 consolidation: mobile menu parity (appearance, help, START FM, MS logout), responsive tour steps for mobile, stageRadius 0 token compliance, AccessDenied.vue in lint fence (2026-09-19)
- [ ] Staging dogfood (internal); the public beta opens at the end of Phase 7

### PR 4.1 + 4.2 + 4.3 notes (2026-09-19)

Built together on `dev`, as one change set.

- **Shell split.** `AdminLayout.vue` keeps only what must survive navigation and be shared (providers, visit
  tracking, `ActionWindow`, `Toaster`, PWA banners, `AdminCommandPalette`) and renders one of two shells:
  `Layouts/Shell/AdminShell.vue` (new) or `Layouts/LegacyAdminShell.vue` (today's sidebar shell moved
  verbatim, `@deprecated`, removed in PR 8.1). The opt-in flag now only chooses the shell and stamps
  `data-surface="admin"`; **no component branches on it** — the palette is restyled once for both shells.
- **Catalog gained `matches`** (route-name patterns per section) so a record page resolves to its workspace
  and tab (`meetings.show` → ViSAK → Posėdžiai). `resolveActive()` (`useAdminNavigation.ts`) and
  `catalogCandidates()` (`AdminNavigationCatalogTest`) implement the same order: params must agree, exact
  beats wildcard. Every `/mano` GET route now resolves to a section or is in `WORKSPACELESS_ROUTES`; no
  route resolves into two workspaces. Cache prefix bumped to `admin-navigation-v2-` so a deploy never
  serves the old payload shape.
- **Shell pieces** (`Components/Layouts/Shell/`): `ShellTopBar`, `WorkspacePicker` (hover + click, Esc,
  brand left rule on the current workspace, its sections listed beneath), `SectionTabs`, `PaletteField`,
  `ShellAccountMenu` (stub), `MobileBottomBar`, `MobileMenuPanel`, `SystemAnnouncement`. Bottom bar is
  in-flow (not fixed); `--shell-bottom-bar` lifts the two legacy fixed save bars (`AdminForm`,
  `EditAgendaItem`) above it, and `--sidebar-width: 0px` removes their 16rem desktop gap. Both unset in the
  legacy shell.
- **Palette (4.3).** Groups: Prisegta · Neseniai (empty state) · Sukurti · Pereiti į, current workspace
  first, workspace as secondary label, visible pin star, full-screen below `sm`.
- **Found and fixed:** the palette's create filter was dead code (ids `create-new_meeting` never matched its
  `create-meeting` map) and every `screen`-target create action (Naujas susitikimas, Posėdžio nebuvo,
  Užbaigti posėdį) did **nothing**. They now open the ActionWindow at the right flow; regression-tested,
  changelog v2.5.
- **`useSidebar()` had three non-sidebar consumers** (`ActionWindow`, `ShowAdminHome`, `DutyUserUpdateWizard`)
  that throw without a `SidebarProvider`. They use `useOptionalSidebar()` now (`@deprecated`, goes in 8.1).
- **Fence:** `Layouts/Shell/**` and `CommandPalette/**` joined `MIGRATED_ADMIN_PATHS`.
- **Verified in a real browser** (`tests/Browser/AdminShellTest.php`, new): workspace + tab on an index and on
  a create page, bottom bar on 390 vs none on 1440, no JS errors. Screenshots at 1440 / 820 / 390 and the
  open picker, palette and Meniu were checked by eye in light mode. **Not done:** dark mode, a real touch
  device, keyboard-only pass, 1180 width. `tests/Pest.php`'s `loginAsAdmin()` now accepts either shell.
- **Not done / left for later:** counts in the picker (catalog is cached 30 min, needs its own source — 4.5);
  `TasksIndicator` still sits in the top bar as round outline pills until 4.5's badges; no help/tour entry
  in the new shell until 4.4 (Pagalba); the account menu is a stub; instant visits and the keyboard set
  (4.6). No spotlight for the shell itself (plan.md's 2.1 note settles that; 4.8 owns onboarding), which
  conflicts with the letter of `AGENTS.md` — worth confirming.
- **Surprises worth knowing:** super admins and comms coordinators get **Svetainė** as the bottom-bar
  workspace (most sections), not ViSAK — the rule says so, worth checking in the rep review. Section tab
  centring raced font swaps (fixed with a `ResizeObserver`). `lang/admin/lt/shell.php` still calls a new
  meeting "Naujas susitikimas" — the glossary retires *susitikimas*; not touched here.
- **Next:** PR 4.4 (account menu, Pagalba, START FM, banners), then 4.5.

### PR 4.5 notes (2026-09-19)

- **Breadcrumbs** (`Shell/ShellBreadcrumbs.vue`, between the tabs and the page): `belowSectionTrail()`
  (`useAdminNavigation.ts`) takes the trail a page registered, drops Pradinis and Administravimas, and
  keeps it from the active section down — so the section is the first, linked crumb and doubles as the
  way back. A section index draws nothing. Phones show only `‹ nearest linked ancestor`.
- **Coverage is the legacy shell's, not better:** only ~40 of ~138 admin pages register a trail
  (`usePageBreadcrumbs`); the rest — most Create/Edit pages, e.g. `institutions.create` — draw nothing
  until their Phase 9 migration registers one. The shell has no fallback on purpose: a page title is not
  available reliably, and a wrong crumb is worse than none.
- **Stale trails:** the breadcrumb state persists across visits (the "no flashing" decision in
  `AdminLayout`), so a page that registers nothing inherits the previous page's trail. When a section is
  active but absent from the trail, nothing is drawn; a stale trail *from the same section* still shows
  (e.g. editing a meeting you just viewed). Clearing on navigation would fix it at the cost of the row
  popping in and out — left for when most pages register.
- **Task badges** (`TaskCountBadge`, `useTaskBadge`): the top-bar `TasksIndicator` is gone from the new
  shell. The count sits on the Pradžia row of the workspace picker, on the **Užduotys** tab, on the
  mobile Užduotys tab, and in the Meniu panel; while the user is *outside* Pradžia it is also on the
  picker trigger, so a rep in ViSAK still sees that something needs them. `attention` normally, `danger`
  (+ clock icon) when any is overdue. The label is folded into the trigger's `aria-label`, which would
  otherwise hide the badge's text.
- **Source of the numbers:** `auth.user.tasks_count` already existed (pending); `overdue_tasks_count`
  is new in `HandleInertiaRequests` — one extra `withCount` on the query that already ran. The old
  indicator's count was really the length of a 5-item API page.
- **Not done:** the bell is still a round outline pill (no decision to restyle it here); other counts in
  the picker (the mock's "3 laukia") need a source — only tasks have one; `data-tour="tasks-indicator"`
  no longer exists in the new shell, so any tour step targeting it is dead until 4.8 retires the tours.
- **Verified:** Vitest (badge, breadcrumbs, `belowSectionTrail`), Storybook a11y stories, a backend test
  for the shared counts, and `tests/Browser/AdminShellTest.php` (needs `npm run build` first — it runs
  against the compiled bundle). Screenshots checked by eye at 1440 and 390, light mode only.

### PR 4.6 notes (2026-09-19)

- **Prefetch + immediate revisit (U2):** every new-shell navigation link — desktop and mobile workspace
  controls, section tabs, account action, and menu rows — keeps hover prefetch and caches it for 15 seconds
  fresh plus one minute stale-while-revalidate. This makes a recently prefetched page render immediately
  without using Inertia's `instant` prop: the catalog links do not carry page components and the existing
  pages expect route-specific props, so a direct instant render would be unsafe.
- **Keyboard set (U3):** `⌘/Ctrl K` opens the existing palette; `/` focuses a collection search when one
  exists; `⌘/Ctrl ↵` submits an `AdminForm`; `Esc` closes the active dialog/sheet; `?` opens the shortcut
  guide. The global handlers ignore typing controls, and the guide is rendered once by `AdminLayout` for
  both shells.
- **Verified:** focused Vitest coverage for shell links, shortcut routing, form saving, and collection
  search; browser checks for `?` and `/`; production build; shortcut guide checked at 1440 px.

### PR 4.8 + 4.9 + 4.10 notes (2026-09-19)

- **PR 4.8 — Welcome tour and spotlight retirement:**
  - Streamlined `ShowAdminHome.vue` welcome tour to 5 focused steps (`workspace-picker`, `command-palette`, `action-create`, `tasks-card`, `account-menu`), matching the new shell top bar targets.
  - Bumped tour identifier to `admin-welcome-v1` in `ShowAdminHome.vue` and aligned LT/EN tutorial translations (`lang/admin/*/tutorials.php`).
  - Retired obsolete sidebar-targeting spotlights in `AppSidebar.vue` (`sidebar-settings-v1`, `reservations-dashboard-v1`, `sidebar-registrations-v1`) and `LegacyAdminShell.vue` (`help-button-v1`).
  - Removed deprecated `resources/css/driver-tour.css` (hardcoded hexes with `!important`) and created clean `resources/css/admin/tour.css` styled with zero radius, hairlines, and brand accent button tokens, imported in `admin.ts`.
- **PR 4.9 — Density preference removal (U20):**
  - Removed density setting and compact classes across frontend components (`useUIPreferences.ts`, `SidebarCustomizeDialog.vue`, `AppSidebar.vue`, and shadcn `ui/sidebar/*` primitives).
  - Removed `density` backend properties and validation from `HasUIPreferences.php`, `UpdateUIPreferencesRequest.php`, and `UserPreferencesApiController.php`.
  - Cleaned up density assertions in `UIPreferencesTest.php`, `useUIPreferences.component.test.ts`, and `SidebarCustomization.component.test.ts`.
- **PR 4.10 — Device split counter (U26):**
  - Migration `daily_device_metrics` table with unique `date`, `phone_logins`, `tablet_logins`, `desktop_logins`, `pwa_launches` counters and timestamps. Strictly zero PII or user IDs stored.
  - Model `DailyDeviceMetric` registered in `MorphMap::MAP`.
  - `DeviceMetricService` with dependency-free coarse User-Agent classification (`desktop`, `phone`, `tablet`), login recording, PWA launch recording, and 30-day recent metrics query with summary percentages.
  - `RecordDeviceLogin` listener listening on `Illuminate\Auth\Events\Login` and recording daily logins by device.
  - PWA launch tracking via PWA manifest `start_url: '/mano?source=pwa'` in `vite.config.mts`, detected in `HandleInertiaRequests` middleware along with `pwa_mode=1` cookie, tracked once per session without duplicate counts.
  - Integrated into Sistema (`/mano/system-status`) via `SystemStatusController` and `SystemStatus.vue` with summary device split cards and recent days breakdown table.
  - Full test coverage in `DeviceMetricTest.php` and `SystemStatusTest.php`.

### PR 4.7 notes (2026-09-19)

- **What ships.** A *direct* GET to `/mano/**` that ends in a 403 now renders `Pages/Admin/AccessDenied.vue`
  (status stays 403) instead of the public blade: the missing permission (`Redaguoti · naujienos` +
  `news.update`), a link to the vusa.lt help pages (`/docs`, `/docs/en`) with "ask your coordinator" as
  the fallback advice, and links to Mano rolės (`profile` for now) and Pradžia. Naming colleagues who can
  grant access was built first and dropped: help pages are the durable answer. Renderer: `bootstrap/app.php`; copy: `lang/admin/*/forbidden.php`.
- **Inertia visits are unchanged** — still a 302 back with an `error` flash → toast (AGENTS.md's
  authorization table). Only the page a person lands on from an email/bookmark explains itself.
- **How the permission is known.** `Gate::after` (`AuthServiceProvider`) stores what a policy denied on the
  request; `ResolveForbiddenExplanation::permissionFor()` maps ability → action and reads the resource from
  `ModelPolicy::resourceName()`. It is only trusted when the 403 wraps an `AuthorizationException`; a bare
  `abort(403, '…')` shows its message instead. **The scope is not named** (`.padalinys` / `.own` / `*`) —
  `commonChecker` tries three and the page says "which action on what", not "which scope".
- **Not covered:** policies that don't extend `ModelPolicy` and closure gates (`can:access-administration`,
  `manage-settings`) have no resource name, so those show only the help link. Route-level `can:` middleware
  ditto. They show the help link only.
- **P9 (5.9) done:** the primary action now points at `profile.roles` (*Mano rolės ir pareigybės*). The help link
  still goes to the docs root; retarget it at a roles page if one is written.
- **Verified:** `tests/Feature/Admin/ForbiddenPageTest.php`, `AccessDenied.component.test.ts`, full
  parallel backend suite. **Not done:** screenshots at the four widths, dark mode, a real browser pass.

## Phase 5 — Pilot slice

Build the page-type layouts **through real pages**, not in the abstract. Every concept is exercised
once; a page type is extracted into a shared layout only after a pilot page proves it.

| # | Pilot | Page type | Proves |
|---|---|---|---|
| P1 | **Shell + Pradžia** | Overview | workspaces from permissions, catalog, attention list + empty state, **+ Sukurti**, palette, bottom bar + Meniu, account menu, START FM, Neseniai redaguota, Tavo koordinatorius (O22), identity and entity markers (O24) |
| P2 | **Posėdžiai** | Collection (Typesense) | `ShowDocuments` anatomy, three views, quick filters, unit as a filter, Rodyti daugiau + restore, URL state, mobile rows |
| P3 | **Meeting record + agenda-item editor** | Record + focused editor | canonical record, key facts, Veikla, ⋯ actions, sequential editing, local side panel, autosave, Papildyk list (R-c), votes as taps (R-e), edit-mode canvas (O24) |
| P4 | **ActionWindow: naujas posėdis** | Guided flow | flow restyle, bottom sheet, date presets |
| P5 | **Duty record + duty form + Priskirti sheet** ✅ | Record + Form + Sheet | occupancy sheet (O21), associations out of the form, reorder mode, text placement, LT/EN switch, Papildomi nustatymai, pickers, save bar, delete in ⋯ |
| P6 | **Rezervacijos** | Collection (database) | table default, preview on a queue, ‹ 3 / 24 ›, bulk selection bar, optimistic approve, status roles |
| P7 | **Žymos** | Collection + sheet form | sheet form, trash filter + undo toast, merge as action |
| P8 | **ViSAK overview** | Overview | section tabs, numbers-as-links, one chart with a text summary, categorical colours |
| P9 | **Paskyra → Mano rolės ir pareigybės** | Account | O14, the target of every 403 explanation, access-change history (U14) |

- [x] **Wave A — the feel** (a rep's day on a phone): P1 → P2 → P3 → P4
- [x] Each pilot page adds its paths to `MIGRATED_ADMIN_PATHS` when it lands
- [ ] **Feel review** — live sessions with 3–5 reps on their own phones ("užfiksuok vakarykštį
      posėdį") and one coordinator; update *The feel* and *Rules*
- [x] **Wave B — the admin concepts:** P5 → P6 → P7 → P8 → P9
- [x] **Concept review;** extract the proven layouts: Overview, Collection, Record, Form, Sheet form,
      Workbench shell; `/mano/search` reduced to cross-entity results (O1)
- [x] Update the [Migration playbook](playbook.md#migration-playbook) with what the pilot taught

### PR 5.1 + 5.2 notes (2026-09-19)

Built together on `dev` from one plan. Verification is split honestly at the end: what a real browser did and did not confirm.

**5.1 · Pradžia**

- `ShowAdminHome.vue` is now a greeting line and bands: `Home/AttentionQueue` (the one ink band, `bg-foreground
  text-background`, only while non-empty — otherwise a teaching `EmptyState`), `CreateShortcuts` (the first three
  `create` entries of `useCommandActions()`, so the catalog stays the only list), upcoming meetings, then a
  deferred group. Cards, gradients, the hero search and the scoped `<style>` are gone.
- **Deferred group `secondary`** (`DashboardController`): `institutionsNeedingAttention`, calendar events, news,
  `recentlyEdited`, `coordinator`. The queue and upcoming meetings stay in the first response (U19). Site content
  (calendar, news) is only assembled for users who may `viewAny` News (R-h).
- `GetRecentlyEditedRecords` (O20): activity log by `causedBy`, deduplicated by root, then filtered through
  `$user->can('view')` so a row never links to a 403. `GetUserCoordinator` (O22): the settings manager role via
  `GetInstitutionManagers`, never the user themselves.
- The task payload gained `taskable {id, name}` so a queue row can say what it is about and link to it
  (`getMeetingAgendaUrl()` first — one tap to the agenda tab).
- New components live in `Components/Home/`, **not** `Pages/Admin/Dashboard/Components/` as first planned: that folder
  holds ~4.5k lines the ViSAK/Rezervacijos dashboards still use, so fencing it would have failed on unrelated files.
  `TasksCard`, `CalendarEventsCard`, `NewsListCard`, `HomeSearchBar` are `@deprecated` (Phase 10). `UpcomingMeetingsCard`
  is untouched — `PersonalOverviewSection` still uses it.

**5.2 · Posėdžiai + `CollectionPage`**

- `Layouts/CollectionPage.vue` composes `Components/Collection/*` (title band, quick filters, control row, filter bar
  — popovers from `md`, bottom sheet below —, active chips, results, load more, view toggle). It knows nothing about
  Typesense: it takes a `CollectionSource` (`Composables/useCollectionSource.ts`). `useTypesenseCollectionSource` is a thin
  adapter over the existing `useAdminCollectionSearch`, which gained one thing: `?pages=N`, so back navigation replays the
  pages that were loaded (capped at 10, reset by any new search). Scroll is saved per URL in `sessionStorage` on leaving.
- View state (`useCollectionView`): URL wins, then the user's last choice per collection, then the entity default; only
  *choices* are stored (`writeDefaults: false`), so an entity can change its default later.
- The view toggle and facet rows are drawn from tokens rather than `ui/toggle-group` / `ui/checkbox`: those still
  hardcode `zinc-*` with `dark:` twins (trap 3) and the 2.8 audit did not cover them.
- **Typesense lag (O1):** `GetRecentlyChangedMeetings` reads the user's own meeting activity from the last 2 minutes and
  pins those meetings above the results (only while no query or filter is active). Not `?just=<id>` as first planned —
  nothing redirects to the collection after a save, so a server read is the only reliable trigger.
- **Trash:** `meetings.index` now returns a lightweight shell (`deletedCount`, `recentlyChanged`); `?showDeleted=true`
  returns the old table as `IndexMeetingTrash`. That is a separate page rather than a second `CollectionSource`: the
  database source has no consumer until Rezervacijos (5.6), and building it now would be the "over-general monster".
- `meetingCompletionStatuses` in `Constants/statuses.ts`. The facet popover and chips are named through the same map
  (`valueLabel`), because the shared facet labels say *Užbaigtas/Neužbaigtas* and the map says *Užpildyta/Neužpildyta*.
  That drift still exists in the legacy search page, `MeetingDataTable` and the Gantt legend (U10 — Phase 9 / 5.10).
- Copy: the row names the format (*Nuotolinis posėdis*, *Sprendimas el. paštu*, no time for e-mail meetings). The create
  action reads **Fiksuoti posėdį** (`shell.actions.new_meeting`). `MeetingType` labels still say *susitikimas* — they feed
  the ActionWindow and a browser test, so they belong to 5.4.

**Backend fixes found on the way**

- `MeetingPolicy::viewAny()` now accepts `meetings.read.own`; the 3.1 note that a plain *Studentų atstovas* could not
  open Posėdžiai is fixed, and `AdminNavigationCatalogTest` now expects it. Which rows a rep sees is still the scoped
  Typesense key's job.
- The SQL completion filter (now trash-only) read every trashed meeting as "external": a nested `meeting.institutions`
  relation drops soft-deleted meetings. Fixed with `withTrashed()`. Its two tests
  (`MeetingStudentPerspectiveTest`, `AgendaItemBreakTest`) now trash their meetings and request `showDeleted`.
- Removed `MeetingController::search()` and `meetings.search`: they rendered `Admin/Representation/SearchMeetings`,
  which never existed.

**Not done**

- **Browser verification is partial.** `tests/Browser/AdminCollectionPagesTest.php` (7 tests, real bundle) confirms both
  pages mount, draw their anatomy, avoid sideways scroll at 390, and throw no JS errors; I also looked at screenshots of
  both pages at 1440 and 390, light mode. **Not done:** 820 and 1180 widths, dark mode in a browser (Storybook axe passes
  in dark for the components only), a real touch device, a keyboard-only pass, and — because the browser cannot reach the
  Typesense host in this environment (`search?…` returns status 0) — **no run against live Typesense**, so real rows, the
  facets, "Mano institucijos" (needs the scoped key's `directInstitutionIds`), the preview pane and back-restore of `?pages=`
  were only exercised through component/composable tests with a fake source.
- `CollectionSelectionBar`, the "Pasirinkti" mode and a checkbox column were **not** built — Posėdžiai has no bulk action,
  so there was no consumer. They land with Rezervacijos (5.6) with `ReservationBulkActionBar` generalised.
- "Papildyk" links to the meeting record, not to the first incomplete agenda item — that list is 5.3's.
- The preview pane reuses the legacy `MeetingDetailPreview` (raw `Badge`, `rounded-*`); restyle it with 5.3 or 5.10.
- Row "Papildyk" is shown to anyone who can see the meeting; the record page decides whether they can edit (there is no
  per-record `update` map on the client).
- Fence: only the files built here joined `MIGRATED_ADMIN_PATHS` (not `Components/Meetings/**` or `Dashboard/Components/**`).
- The `docs/` page for Pradžia/Posėdžiai (playbook step 11) and retiring the `HomeSearchBar` test.

### PR 5.3 notes (2026-09-19)

**5.3 · Meeting record + agenda editor + extract `RecordPage`**

- **`Layouts/RecordPage.vue`:** The canonical record page layout. Composes title band (identity slot, `EntityTypeMark`,
  title, `StatusBadge`, subtitle slot), record facts strip (`dl` with 3–6 key facts), alert slot (`#alert`), desktop section
  tabs (for ≥ 3 sections) / mobile vertical stack, primary action control, and `⋯` overflow action menu (dropdown on desktop,
  bottom sheet on mobile).
- **`ShowMeeting.vue`:** Rebuilt on `RecordPage`. Features `MeetingDatePlate`, key facts (date, institution, participants,
  public visibility), `MeetingCompletionChecklist` (actions for incomplete agenda/votes), deferred documents (`MeetingDocumentsPanel`),
  SharePoint files (`FileManager`), deferred tasks (`TaskManager`), and `RecordActivity` (activity log and discussion).
- **`EditAgendaItem.vue`:** Dedicated agenda item editor with sequential navigation (`AgendaItemNavigator`), edit mode switch
  tinting the canvas (`bg-secondary`), auto-resizing title textarea, `AgendaItemBody`, `DiscussionPanel`, notes sidebar
  (`AgendaItemNotesSidebar`) on desktop and bottom sheet on mobile, autosave (`useAgendaItemAutosave`), and sticky bottom save bar.
- **`RecordActivity.vue`:** Unified activity and discussion feed for record pages, rendering timeline events and comments.
- **`MeetingCompletionService`:** Evaluates completeness of meetings and agenda items (missing dates, lack of agenda items,
  missing votes, missing student perspective).
- **Fence:** `RecordPage*.vue`, `RecordActivity.vue`, `ShowMeeting.vue`, `EditAgendaItem.vue`, `MeetingDatePlate.vue`, and
  `MeetingCompletionChecklist.vue` enrolled into `MIGRATED_ADMIN_PATHS`.

### PR 5.4 notes (2026-09-19)

**ActionWindow: naujas posėdis** — the guided flow restyled through tokens, not re-architected: same screens, stack and draft.

- **Tokens, no decoration.** Gradients, `rounded-*` and raw hues are gone from the window. Choices are hairline rows
  (`ActionChoiceList` draws one `divide-y` list, not a box per choice) with a flat 40px tile; `ActionChoiceButton` takes a
  status `tone` instead of `gradient`. Institution rows use the `institutionActivityStatuses` roles, and the healthy state
  is plain (status rule: don't paint every row). The check-in explainer is a neutral note, not amber — nothing is wrong.
- **Header says which job and how far.** `EntityTypeMark` (meeting) + a flow label from `screenRegistry`, `2 / 5` text and a
  segment bar under the header — the current segment is `bg-brand-fill` (the location marker), so the primary button and the
  marker are the only brand fills. Amendments from the review still show no progress.
- **One primary button.** `ActionWindowPrimaryButton` (brand, uppercase, 44px on coarse, `loading` spinner) replaces six
  hand-rolled footers. `ReviewRow`/`AgendaItemsEditor`/back/close buttons got `pointer-coarse:` sizes.
- **Date presets (rules/pages.md).** `MeetingWhenScreen` always leads with **Šiandien** and **Vakar**, then the body's usual
  slot (unchanged), then "Kita data…". A day preset carries the hour already chosen → the body's usual hour → 18:00, and goes
  to the time screen (email meetings store the 23:59 deadline and skip it). The screen no longer auto-skips to the calendar,
  because there is always something to offer. `MeetingDateScreen` uses the shared `DatePicker` (typed field + popover; native
  on coarse pointers) instead of an inline calendar, and both screens now carry `returnTo`, so changing the day from the
  review returns to the review.
- **`DatePicker` speaks UTC-noon dates**; `toPickerDate()` (in `useWindowDates`) seeds it. The check-in range used
  `new Date()`, which displayed the previous day between 00:00 and 03:00 in Lithuania.
- **Bottom sheet.** Below `md` the window stays a vaul `Drawer` (swipe to dismiss) at 92dvh with a hairline top edge; from
  `md` a bounded dialog. The primitive's own grabber still hardcodes `zinc-*` (`ui/drawer` was not in the 2.8 audit).
- **Copy (glossary).** `MeetingType` labels: *Gyvas posėdis*, *Nuotolinis posėdis*, *Sprendimas el. paštu*; the review button
  is **Fiksuoti posėdį** (matches `shell.actions.new_meeting`). `meeting.when.title` reads *Kada vyko ar vyks posėdis?*.
- **Catalog.** `useActionWindowCatalog` lost its `gradient` fields; `quickActionGradient` stays for the legacy sidebar
  (Phase 8). `ActionWindowTrigger` (legacy sidebar entry) is deliberately outside the lint fence and keeps its tint.
- **Found by Storybook axe:** `ReviewRow` put its button directly inside `<dl>`; it now lives in the `dd`.
- **Browser test was stale**, not only for the labels: it still expected `action_window.actions.*` titles, but the catalog
  has read `shell.actions.*` since 5.2. Fixed; it passes against a fresh `npm run build`.
- **Fence:** the window's components, `screens/**` and `screenRegistry.ts` joined `MIGRATED_ADMIN_PATHS`.
- **Verified:** Vitest (component + Storybook a11y in light and dark for the new `ActionWindow.stories.ts`), full parallel
  backend suite, `tests/Browser/ActionWindowTest.php`, and screenshots at 1440 and 390 (light) of the institution, type,
  when and date screens through the new shell. One Vitest failure is not from this PR: `DiscussionPanel` (*posts a new root
  comment*) fails identically on a clean tree.

**Not done**

- 820 and 1180 widths, dark mode in a real browser, a touch device (native date/time fields are untested), a keyboard-only pass.
- The agenda, review, check-in and "complete a meeting" screens were not walked in a browser (covered by component tests and,
  for the review, a story).
- The window is still opened from the legacy sidebar trigger and Pradžia's shortcuts; the mobile bottom bar's centre **+**
  opens the same window, which was not screenshotted.

**Next:** the feel review with reps (no PR) — the flow to try is "užfiksuok vakarykštį posėdį"; findings land here as plan edits.

### PR 5.5 notes (2026-09-20)

**5.5 · Duty record + form + Priskirti sheet (O21) + extract `FormPage` and `SheetForm`**

- **`Layouts/FormPage.vue`:** single column (~40rem) on the tinted edit canvas (`bg-secondary`) with a *Redaguoji* / *Kuri naują*
  eyebrow, back link, optional LT | EN switch, `#advanced` disclosure (*Papildomi nustatymai*), `#danger-zone`, sticky save bar.
  **⌘/Ctrl + Enter** submits and **Esc** cancels (listeners on the `<form>`, so a portaled Select never cancels it). On a failed
  submit the error summary is scrolled into view and focused; each message focuses its field (`field-ids` maps `name.lt` →
  `duty-name`). `mode="create"` never claims "all saved". The save button is tied to the form with `form=`, not a click handler.
- **`Patterns/SheetForm.vue`:** right sheet, bottom sheet below `md` (`92dvh`, safe-area padding), `#danger-zone` in the body (never
  the footer), and a `dirty` prop: Esc, the overlay and *Atšaukti* ask before discarding. `Patterns/FormSection.vue` is a
  `<section aria-labelledby>` (no `<fieldset>`/`<legend>`). `Patterns/ConfirmDialog.vue` is the one confirmation (verb on the
  button, destructive variant with its `dark:` twin) — no native `confirm()` remains on these pages.
- **`Features/Admin/Occupancy`:** `AssignDutyUserSheet` (create + edit a term), `MemberSearchField` (real combobox: arrow keys,
  Enter selects and never submits the sheet, in-flow list so the scrolling body cannot clip it, already-assigned members offered
  but not selectable), `MemberTermRow`, and `termStatus()` (current / upcoming / ended, mirroring `Duty::current_users()`).
  The sheet holds ISO `YYYY-MM-DD` strings in Vilnius time (`todayIso()`), edits the public description as LT/EN Tiptap, warns
  without blocking when every place is taken, and keeps ending a term / deleting it in a danger zone behind confirmations.
  Ex-officio terms lock their dates and offer neither.
- **`ShowDuty.vue`:** on `RecordPage`. The healthy state (occupied) has **no badge**; a vacant duty gets one status and one alert.
  Members are split *Dabartiniai / Būsimi / Kadencijų istorija* by term (a future-dated member no longer counts as a holder); the
  Nariai count and the *Vietos* fact count who serves today. Sibling duties and study programs are deferred (`dutyPanels`) behind a
  skeleton; the two unused next/last-meeting queries are gone. Ending a tenure goes through the access-change guard.
- **`DutyForm` / `CreateDuty` / `EditDuty`:** on `FormPage`; the members `TransferList` is gone (O21, Forms 1 and 15), delete asks
  for confirmation. `TransferList` and `FormUpsertLayout` carry `@deprecated` (Phase 9.3 / Phase 10).
- **Backend:**
  - `dutiables.store` (`StoreDutiableRequest`, authorised through `DutyPolicy::managePeople`) rejects a member who already holds
    the duty in an overlapping period, stamps `tenant_id` for a cross-tenant admin's **delegated seat** (otherwise the owning
    tenant's next save read it as its own), enforces that tenant's **quota**, goes through the model's sanitising setter for the
    public description, and is written to the duty's activity log like the wizard's attach.
  - `end_date` is `after_or_equal` `start_date` on store **and** update (a term can end the day it starts).
  - `DutyController::update` keeps members when `current_users` is absent; `show()` defers `otherDuties` + `studyPrograms`.
- **Found while reviewing 5.5's first cut** (all fixed, all covered by tests): the sheet's member search never worked (the endpoint
  requires `permission`, the sheet did not send it); a written note failed validation (`description` is a translatable array) and
  an existing one rendered as `[object Object]`; the study-program picker never rendered; Enter in the search box submitted the
  sheet; dates used the UTC day; *Baigti kadenciją* skipped the access-change guard; six copy keys were missing in English and
  much of the copy was not *tu* / used retired glossary words (*asmuo*, *naudotojas*).
- **Fence:** `FormPage`, `SheetForm`, `FormSection`, `ConfirmDialog`, `Features/Admin/Occupancy/**`, `ShowDuty`, `CreateDuty`,
  `EditDuty`, `DutyForm` are in `MIGRATED_ADMIN_PATHS`.

**Not done**

- **No browser verification.** Vitest (component), Storybook a11y (`Patterns/Form surfaces`) and the backend suite cover it; nobody has
  yet looked at the duty page, the form or the sheet at 390 · 820 · 1180 · 1440, in dark mode in a real browser, on a touch device,
  keyboard-only, or run the persona pass (Rep / Rep + comms / Resources / Super admin).
- **Laravel Precognition for `DutyForm`:** `StoreDutyRequest`/`UpdateDutyRequest` have no server-only rules (uniqueness, date
  conflicts); the duplicate-name check is `DuplicateDutyWarning`. Add `withPrecognition()` when such a rule appears.
- **Per-field translation peek (O9)** and per-section "EN: trūksta N laukų" beyond the name are not built; `FormPage` shows a dot on
  the locale tab and `FormSection` accepts `missing-count`, but `DutyForm` only reports the name.
- **Rule 5 of Pickers** (offer *Sukurti naują* inside the institution picker) is untouched.
- The user search still uses `scope=all` like the duty wizard (a member from another unit is how they join a new one); their e-mail
  is masked by the endpoint.
- The `docs/` user page for duties (`_parts/duties`) is one sentence and was not extended; retiring tours/spotlights that point at
  the old duty form was not needed (none exist).
- `MemberSearchField` has no mobile "replace the sheet's content" step (Pickers rule 6) — the in-flow list avoids stacking instead.

### PR 5.8 + 5.9 + 5.10 notes (2026-09-20)

Built together on `dev`, one commit. Decisions taken with the user before building: the ViSAK Gantt stays as a lazily
rendered section (page tabs, both tours and the spotlights go); *Mano rolės* is an aggregate view, not per-duty
resolution; search keeps single-collection views only for the tabs with no page of their own.

**5.8 · `OverviewPage` + ViSAK overview**

- **`Layouts/OverviewPage.vue`:** bare root (no `AdminContentPage`, like Pradžia, so it works in both shells); eyebrow,
  title, lead, `#heading` (Pradžia's greeting), `#actions`, `#attention`, default. **Its h1 is `text-2xl`/`text-3xl` in regular
  case, not `u-display`:** `u-display` renders heavy uppercase in the admin surface, spent the uppercase budget on a title
  and overlapped the eyebrow (found in a screenshot). `CollectionTitleBand` and Pradžia already read this way. Sibling
  pieces: `Components/Overview/OverviewNumbers.vue` (one component, not the planned Numbers + Number pair),
  `OverviewChart.vue` (a `<figure>` whose caption is required), `OverviewScopeSwitch.vue`. **`Home/HomeSection` was promoted
  to `Patterns/OverviewSection`** (its `data-slot` is now `overview-section`, which the Pradžia browser test waits for).
- **Consumers:** ViSAK, Pradžia (light retrofit, behaviour unchanged), *Mano rolės* and Paieška — four pages on the layout.
- **ViSAK page** (`ShowAtstovavimas.vue`, 752 → 563 lines; most of what is left is the Gantt's lookup maps): title band with a **visible scope
  switch** *Mano institucijos · Padalinys* (URL `?scope=`, `?tab=tenant` still honoured) replacing the two `ui/tabs`; the
  attention list (`InstitutionsNeedingAttention`, not an ink band — it is not an alert); four numbers, each a link
  (*Vėluoja*, *Artėja terminas*, *Neužpildyti posėdžiai* → `meetings.index?completion_status=incomplete`, *Atviros užduotys*;
  tenant scope swaps the third for *Nėra duomenų*); one chart with a generated sentence (`summarizeStatusTrend`); upcoming
  meetings; the Gantt behind an intersection observer with a skeleton. Only backend change: `openTasksCount`.
- **The chart's series are statuses**, so they use `--status-*` roles rather than `--cat-*` (visual.md keeps categories out of
  status meaning). It appears **in the tenant scope only**: the history API is tenant-wide, so showing it beside a rep's own
  institutions would mislead. A rep with no visible tenants therefore sees numbers but no chart.
- **Below `md`** the timeline is replaced by one line and a link to Posėdžiai. The fullscreen modal and gap → ActionWindow /
  check-in actions stay — they serve the Gantt.
- **Retired:** the two product tours, three spotlights, breadcrumbs (below section level now), the gradient wordmark and its
  glow timer. `@deprecated` (Phase 10): `PersonalOverviewSection`, `InstitutionStatusSummary`, `InstitutionDataTable`,
  `MeetingDataTable`, `InstitutionsOverviewCard`, `UpcomingMeetingsCard`, `VisakInfoModal`. Orphaned `tutorials.php` /
  `visak.php` keys were left in place.

**5.9 · Mano rolės ir pareigybės**

- `GET /mano/profile/roles` (`profile.roles`), `ProfileController::roles()`, `App\Actions\GetUserAccessSummary`. It reads the
  dutiable rows, not `User::duties()`, because the pivot there omits `tenant_id` and `via_dutiable_id` — the two facts that mark
  a cross-tenant representative and an ex-officio seat. Terms are split current / upcoming / ended (last 10). A link to a duty or
  institution is emitted only when the user may `view` it, so the page never leads to a 403.
- **Role names are translation keys** (`$t(role.name)`, as the old profile list did), so the page translates them.
- **"Ką gali daryti" is the catalog** (`adminNavigation`), aggregated — not per duty. The catalog gates on a whole `User`;
  making it duty-aware would be a tier-A change to the contract every menu reads, which one page does not justify.
- Wired into the account menu, the mobile Meniu, the palette (`nav-roles`) and `AccessDenied` (button and hint copy). The
  hard-coded, untranslated "Tavo rolės" list on the profile page became a link. **No spotlight**, per the playbook (a returning
  user would look in the old place, which now links here) over AGENTS.md's blanket rule.
- New route allowlisted in `AdminNavigationCatalogTest` (workspace-less, excluded from the catalog).

**5.10 · `/mano/search` cross-entity only**

- `Paieška` is one field over one group per entity (≤ 5 rows, static order, empty groups hidden) with **Rodyti visus (N)**.
  Each group's destination and **query key** come from `App\Support\AdminSearchDestinations`: Typesense collection pages read `q`,
  database table pages read `search`. A link is `null` when the user may not open that list.
- `SearchController::index` redirects a `?tab=` for an entity with its own page; **`agenda-items` and `resources` keep their
  single-collection view** (`SearchCollectionPanel`) until Phase 9. An unknown tab falls back to the search page instead of a 422.
  The four legacy `search.*` routes go straight to the final page. `can.create` is gone: creating lives in **+ Sukurti**.
- **U10 drift:** the facet labels now read `meetingCompletionStatuses`; the duplicate in `AdminFacetMerger` (no caller) is gone;
  the Gantt legend says *Užpildytas / Neužpildytas posėdis*; `MeetingDetailPreview` uses `StatusBadge` and the U10 maps and
  is fenced. Its vote-alignment badge is a neutral `StatusBadge` — no U10 map for alignment exists yet, and inventing colours
  for it was not this PR's call.
- `SearchTabs` and `SearchAllPanel` are `@deprecated` (they keep their tests).

**Fence added:** `OverviewPage`, `Components/Overview/**`, `OverviewSection`, `ShowAtstovavimas`, `InstitutionStatusTrendChart`,
`ShowMyRoles`, `MyDutyTermRow`, `SearchIndex`, `SearchResultGroup`, `MeetingDetailPreview`.

**Verified:** Vitest (full), Storybook a11y (light + dark, incl. `Overview.stories.ts`), backend suite, and
`tests/Browser/AdminCollectionPagesTest.php` against a fresh build (seven new tests: mounts, no JS errors, no sideways scroll at 390).
I also looked at screenshots of ViSAK, *Mano rolės* and Paieška at 1440 and 390, light mode.

**Failing tests that predate this work** (both from `7a69414c`, not touched here): `AdminEntityTranslationsTest` expects 22 index
pages declaring `entityName` but finds 20 (`IndexReservation` and `IndexTag` no longer declare it), and `DocsCoverageCommandTest`
expects every route in a filtered area to start with `reservations`, which `api.v1.admin.reservations.index` does not.

**Not done**

- 820 and 1180 widths, dark mode and touch in a real browser, a keyboard-only pass, the four-persona pass, and a run against live
  Typesense (the browser cannot reach it here: the search page showed its error line, so grouped results, see-all links and the
  retained tabs were exercised only through component tests with a fake `multiSearch`).
- **O15's "Stebimos institucijos" list** on the ViSAK overview — followed institutions are not in the page's props.
- The Gantt on a phone is a link, not a simplified read-only view.
- Per-duty section resolution and the dated *access-change history* (PR 7.7).
- The `docs/` user pages: there is no ViSAK or roles page to update; only the changelog.
- Orphaned copy keys (`tutorials.php`, `visak.php`) and the dead `FacetCheckboxList`, `FacetYearPills`, `useRecentSearchHits` were left alone.

### Phase 5 review & robustness polish (2026-09-20)

Follow-up review of all Phase 5 pilot surfaces (P1–P10) to verify robustness, completeness, and adherence to redesign rules.

- **Backend test & route resolution fixes:**
  - `AdminEntityTranslationsTest`: Declared `const entityName = 'reservation';` in `IndexReservation.vue` and `const entityName = 'tag';` in `IndexTag.vue`, restoring full 22/22 index coverage and translation tests.
  - `DocsCoverageCommandTest`: Updated route filter in `tests/Feature/Docs/DocsCoverageCommandTest.php` to accept `api.v1.admin.*` routes mapped to feature areas (e.g. `api.v1.admin.reservations.*`).
- **Visual budget & token discipline:**
  - `RecordPage.vue`: Replaced `.u-display` on `<h1>` with regular-case `min-w-0 text-2xl font-semibold tracking-tight text-foreground sm:text-3xl lg:text-4xl`, preventing uppercase heading bleed and adhering to the visual budget (uppercase reserved only for eyebrows, workspace tabs, and primary buttons).
  - `ShowMeeting.vue`: Replaced raw `text-zinc-500` with semantic token `text-muted-foreground`.
  - `eslint.config.mjs`: Upgraded custom `no-legacy-utility` lint rule to inspect Vue template static attributes (`VAttribute`) via parser services template body visitor, ensuring template class strings obey token rules.
- **Collection lifecycle & trash workflows:**
  - `useCollectionSource.ts`: Added reactive watcher on `options.initial.items` in `useDatabaseCollectionSource` so that Inertia page navigations and partial prop reloads update local state and pagination.
  - `IndexReservation.vue` and `IndexTag.vue`: Completed soft-deletion workflows (`showDeleted` prop, back buttons "‹ Visos...", trash preview actions with `ConfirmDialog` for force deletion, single/batch restoration), and added row selection checkboxes in `#row` for mobile viewports (< md).
  - `ShowAdminHome.vue`: Broadened `hasAtstovavimas` check (`props.upcomingMeetings?.length || auth?.can?.create?.meeting || auth?.can?.index?.meeting`) so reps with upcoming meetings never have their meetings panel hidden.

## Phase 6 — Messages (email, push, in-app)

See [Messages](messages.md#messages--email-push-and-in-app). Done before the beta opens, because the beta's
reps will meet the app through these.

- [x] Extend `BaseNotification` with `primaryAction()` and `context()`; one content contract, three
      renderings (rule 1)
- [x] Brand the mail layout (`resources/views/vendor/mail/html/*`): square, hairlines, red button,
      wordmark, ~600px, light-only
- [x] Retire the bespoke blades that exist only because the layout was ugly (`comment-posted`,
      `assigned-to-resource`, `feedback`, the registration pairs, `reminder-to-login`) — keep only what
      genuinely differs
- [x] Digest template on the same structure (grouped by category; title + context + link per item)
- [x] Per-type pass over the 24 notifications with the channel-policy table: subject, primary action,
      context rows, channel, signature (rules 2–9)
- [x] "Why you got this" footer + link to Pranešimų nustatymai; plain-text alternative checked
- [x] Push payloads follow the same contract; quiet hours 22:00–07:00 for non-urgent (rule 13)
- [x] Remap `NotificationCategory::color()` onto `--cat-*`
- [x] In-app notification list renders the shared contract (O12: notifications = what happened)
- [ ] Screenshots of every template from Mailpit at 360px and desktop, both locales

### PR 6.1 notes (2026-09-20)

- **`actions()` is now derived.** `primaryAction()` / `secondaryAction()` are the source; `actions()`
  stays on `BaseNotification` as a `@deprecated` accessor (removal: Phase 10) so the stored
  `notifications.data.actions` key keeps its shape. `NotificationContractTest` fails if a subclass
  overrides it again. `InstitutionActivityNotification` is the only class with a secondary action.
- **Legacy rows.** Every notification stored before this PR has no `primaryAction`; the frontend
  readers fall back to `actions[0]` / `actions[1]`. Drop the fallback with `actions()`.
- **`context()` is populated for the act-tier types only** (task assigned / overdue / reminder,
  meeting reminder, periodicity gap, approval requested, assigned to resource, duty expiring, comment).
  Know/record types stay empty for 6.3/6.4. Rows go through `BaseNotification::contextRows()`, which
  drops blank values; labels are `notifications.context.*` (registered as a dynamic prefix in
  `TranslationIntegrityTest`). `TaskAssigned` and `DutyExpiring` read one relation each
  (`taskable`, `institution`) — everything else comes from constructor data.
- **Rendering.** `/mano/notifications` cards show context rows and the action(s); the bell shows the
  primary action only, as a sibling of the row button. Toast unchanged. `toDigestItem()` also stores
  `context` and `primaryAction` so 6.2 has them; `toMail()` uses the primary action for its button.
  `toWebPush()` is untouched (6.5).
- **Deleted** the four unused `Features/Admin/Notifications` components (`NotificationItem`,
  `NotificationTypes/*`) and `canMuteNotification`. The `notifications.mute_thread` /
  `unmute_thread` lang keys are now unused; left for the Pranešimai pass (9.9).
- **Not fixed, on purpose:** copy that breaks the glossary (`action_view_meeting` "susitikimą",
  `action_view_resource` "resursą", `MeetingType::label()`) — per-type copy is 6.3/6.4.

### PR 6.2 + 6.3 + 6.4 + 6.5 notes (2026-09-20)

Shipped as one commit.

- **One template, one theme.** `emails/notification.blade.php` renders the 6.1 contract (title, body,
  `x-mail::context` rows, primary button, secondary link, muted subcopy with signature and "why you got
  this"). `vendor/mail/html/themes/default.css` is square, hairline, light-only, one brand fill (the
  button); the wordmark is text. `comment-posted` and `assigned-to-resource` are deleted with their
  `toMail()` overrides. **Kept**: `feedback`, `reminder-to-login`, both registration pairs — long-form
  prose, and they inherit the brand through `x-mail::message`. New `vendor/mail/text/context` and
  `text/digest-category` exist because the text render resolves `mail::` to the `text/` directory; without
  them the plain-text alternative is raw table markup (the old digest's was).
- **`NotificationUrgency` (Act / Know / Record / Onboarding) decides the channels.** `via()` is now the one
  implementation: in-app always; push when the tier or `sendsPush()` says so and the user's push toggle
  allows; immediate mail for `Act` when the user's email toggle allows. `TaskAssigned` is `Act` only when
  due within 7 days; `CommentPosted` is `Act` only for a mention (`isMention`). This **removes push for
  know/record types**, and finally makes the push toggle bite for every category (it was ignored outside
  News/Calendar).
- **One email toggle** (`email_digest` preference, relabelled "El. paštas"): on = act-tier mails at once
  *and* know-tier lands in the digest; off = neither. A user cannot keep the digest while refusing instant mail.
- **Quiet hours** (`App\Support\QuietHours`, 22:00–07:00 `Europe/Vilnius`) hold **push** (`withDelay()`) and
  **digest dispatch** (`notifications:send-digests` returns early). In-app and act-tier mail are never
  delayed. Digest-command tests pin the clock to midday.
- **Signatures**: `BaseNotification::mailSignature()`; `MeetingReminder`, `InstitutionActivity`,
  `TaskAssigned` (institution tasks) and `DutyExpiring` sign as the institution's coordinator
  (`GetInstitutionManagers`), on the duty address (`NotificationRouter::preferredEmail()`), never the
  recipient themself. No person → "Mano VU SA".
- **`NotificationCategory::color()`** returns `cat-1…8` / `neutral`; `colorHex()` mirrors the light-mode tokens
  for email. `useNotificationFormatting.ts` maps stored hue names onto tokens (`legacyHueToToken`,
  `@deprecated`, Phase 10). The old System `red` maps to neutral.
- **Deliberately not done / deviations from `messages.md`:**
  - `NewsPublished` and `CalendarReminder` were **removed** (2026-09-20, scope cut): the notifications,
    their `notifications:send-news` / `notifications:calendar-reminders` commands and schedule, the
    `News` / `Calendar` categories, the `calendar_reminder_hours` preference and their settings-page rows.
    Stored preference keys and old notification rows are left in place; nothing reads them. Reinstate
    them (and their `messages.md` rows) if a news or event notification comes back. `Welcome` stays
    in-app only (rule 14: no action, no deadline) rather than emailing as the table says.
  - **Rule 8 (locale follows recipient) is not met**: `users` stores no locale, so queued mail renders in
    the app default. Needs a locale column; open question.
  - **Subjects are the existing titles**, cut to 60 characters, emoji prefix removed. Titles that carry no
    object ("Nauja užduotis") were not rewritten to front-load it — that needs the title to take parameters.
  - Copy pass covers the notification and preferences strings (*tu*, posėdis / išteklius, `MeetingType::label()`);
    other `lang/admin` files were not touched.
  - Mailpit screenshots at 360px / desktop, both locales, are a manual gate item and are **not** in this commit.
  - Answerable email buttons (U21) remain Phase 7.10.

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
