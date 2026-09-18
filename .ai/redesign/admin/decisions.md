# Decisions — what is settled and why

> **Admin redesign.** Read when: you need the reasoning behind a rule, or you think a decision should change.
> Index: [README.md](README.md) · Rules are the short form: [rules/](rules/)

## Settled decisions

### Round 1 — structure (2026-09-17)

| # | Decision |
|---|---|
| D1 | **No global sidebar.** Top bar + workspaces. Workbench pages may keep local side panels. |
| D2 | **Workspaces built from permissions**, and inside a workspace only the sections and create actions the user holds permissions for. |
| D3 | **Six workspaces** — Pradžia, ViSAK, Rezervacijos, Svetainė, Organizacija, Sistema. |
| D4 | **Home's attention queue is Tasks.** No photo hero. Attention items and **+** in the first mobile viewport. Empty = one line. |
| D5 | **Command palette is an accelerator, never the only path.** One catalog feeds every surface. |
| D6 | **One create entry** (**+ Sukurti**) → ActionWindow. Guided flow for infrequent ≤ ~6-decision tasks; full form otherwise. |
| D7 | **Operational type scale and uppercase budget.** |
| D8 | **Status colours never use the brand hue**; warning/danger clearly distinct from dark-mode amber; always icon + text. |
| D9 | **Square everywhere**, inputs and checkboxes included, strong focus rings. |
| D10 | **Same font as public**, `tabular-nums` in tables, `AccessibilityMenu` in the account menu. |
| D11 | **Top bar from `md` up; bottom bar below `md`** for everyone. Tablets first-class. |
| D12 | **Staging dogfood → time-boxed opt-in beta (2–4 weeks, fixed end date) → one switch.** |
| D13 | **Sidebar customisation is dropped.** |

### Round 2 — pages and behaviour (2026-09-17)

Details and rationale under [Decision details](#decision-details).

| # | Status | Decision |
|---|---|---|
| O1 | ✅ | One collection page per entity (search tabs + index tables merged). Rows, table and preview available on every collection (rows only below `md`, preview from `xl`); defaults per entity. |
| O2 | ✅ | Preview pane is the default only for queues; **‹ 3 / 24 ›** on records opened from a filtered collection. |
| O3 | ✅ | Collection anatomy follows public `ShowDocuments.vue`; "Rodyti daugiau" everywhere, restored on back. |
| O4 | ✅ | One canonical page per record. |
| O5 | ✅ | Keep the focused agenda-item editor (it already has previous/next); redesign it as the sequential-editing reference. |
| O6 | ✅ | Comments + change history as one **Veikla** timeline. |
| O7 | ✅ | Small entities create/edit in a sheet. |
| O8 | ✅ | Form anatomy + [form rules](rules/pages.md#forms), incl. composition vs association and reorder mode. |
| O9 | ✅ | Form-level **LT \| EN** switch with missing-translation indicators. |
| O10 | ✅ | Merge tools become actions. |
| O11 | ✅ | Soft delete → undo toast; trash is a filter. |
| O12 | ✅ | Tasks = must do (Pradžia); notifications = happened (bell); tasks counter leaves the top bar. |
| O13 | ✅ | One **Pagalba** menu; onboarding reset. |
| O14 | ✅ | **Mano rolės ir pareigybės** in Paskyra. |
| O15 | ✅ | Shell extras table; START FM stays as a discoverable extra. |
| O16 | ✅ | Toasts only for the user's own actions; inline bands for persistent information; one empty state, content-shaped skeletons, one progress bar. |
| O17 | ✅ | ≤ 3–4 numbers per overview, each a link; charts only for trends. |
| O18 | ✅ | *Tu*, verbs, Lithuanian source keys; glossary settled — posėdis, renginys, narys, išteklius, sekretorius, koordinatorius. |
| O19 | ✅ | The navigation catalog lives on the server. |
| O20 | ✅ | Recently visited → palette only; Pradžia shows *Neseniai redaguota*. |
| O21 | ✅ | One occupancy object and one **Priskirti** sheet for putting people in duties; the wizard becomes the bulk term change. |
| O22 | ✅ | Two roles, not one: **koordinatorius** = `Institution::managers()` (the settings role) answers "who do I ask"; the per-term nominee becomes **sekretorius** (`InstitutionAdministrator` → `InstitutionSecretary`). |
| O23 | ✅ | Colour system: six status roles, eight categories, concrete tokens; meaning carried by shape as well as hue. |
| O24 | ✅ | Distinct pages through identity anchors, entity markers, silhouettes and an unmistakable edit mode. |
| O25 | ✅ | Workspaces are switched through a mega-panel picker (like the public `PadalinysSelector`), replacing the tab row. |
| O26 | ✅ | **Messages are in scope:** email, push and in-app share one content contract — see [Messages](messages.md#messages--email-push-and-in-app). |

### From v0 — kept / not kept

- **Kept:** one visual language with public; attention-first home; hairline lists over card grids;
  mobile bottom bar with a centre **+** and a bottom sheet; table → rows below `md`; filter bar with
  a count and clear-filters; one question per screen (ActionWindow).
- **Not kept:** the two-way view toggle; photo heroes on work screens; `u-display` greeting at 6xl;
  all-caps statuses/table headers/secondary buttons; 10px text; brand colour as status; hover-only
  affordances; view state that is not a URL; a rep-shaped bottom bar for every role; a header
  search input beside the palette.

---

## Decision details

### Finding things

**O1. One collection page per entity.** ✅
- *Today:* `/mano/search` has 10 entity tabs (Typesense, facets, a list + detail split view, its
  own create buttons). 23 pages use `IndexTablePage` (database, TanStack, trash, bulk). Meetings,
  institutions, duties, users, news, pages, calendar and documents have **both**; `resources.index`
  already redirects into search.
- *Settled:* one collection page per entity at its index route, one UI whatever the backend;
  `/mano/search` becomes cross-entity only; Typesense for text-heavy, high-volume entities,
  database for small config lists; trash always reads the database.
- *Views — settled:* **three views of one collection, all available on every collection**; the entity
  declares only its default:
  - **Rows** — the `ShowDocuments` list look: title, status, 2–3 meta, one action. Readable on a
    phone. Default for scanning entities.
  - **Table** — ≥ `md`, for comparing attributes, sorting by column and bulk work. Default where
    that is the job.
  - **Preview pane** — the Typesense admin detail previews beside the list, from `xl`; the default
    only on queues (O2). `GenericDetailPreview` covers entities without a bespoke preview. The
    preview components also stay the backbone of record pickers (`CollectionSelectDialog`).
  - Defaults, validated in the Phase 0 inventory:

    | Entity | Views (default first) | Backend | Preview |
    |---|---|---|---|
    | Posėdžiai, Institucijos, Naujienos, Puslapiai, Kalendorius, Dokumentai | rows | Typesense | — |
    | Darbotvarkės klausimai | rows | Typesense | ✓ |
    | Nariai, Pareigybės | table, rows | Typesense | — |
    | Rezervacijos | table, rows | database | ✓ |
    | Ištekliai | rows (with photo), table | Typesense | — |
    | Problemos, Pagalbos užklausos | rows | database | ✓ |
    | Žymos, Tipai, Kategorijos, Renginių tipai, Ryšiai | table | database | — (sheet form) |

- *Trap:* Typesense is eventually consistent — after create/update the user must see their change
  at once (optimistic row, or a database read for "changed by me in the last minute").

**O2. Preview pane vs opening the record.** ✅
Default opens the record. The preview pane is the default view only on queues; it is available on
every collection from `xl`, never on mobile. **‹ 3 / 24 ›** on a
record opened from a filtered collection.

**O3. Collection anatomy.** ✅
Adopt the `ShowDocuments` anatomy (see [Collections](rules/pages.md#collections)); remember last filters/sort/view
per user per collection; no named saved views; a thin top progress bar instead of the blurred
loading overlay. **"Rodyti daugiau" everywhere**, with loaded rows and scroll restored on back.

### Records

**O4. One canonical page per record.** ✅
Work objects (meeting, institution, duty, user, problem, reservation, form, support request) → record
page; "Redaguoti" leads to the form. Content objects (news, pages, calendar, banners) → the editor,
with "Peržiūrėti viešai". Lists link to the canonical page only.

**O5. The focused agenda-item editor.** ✅
*Correction:* it already works as a focused editor — `AgendaItemNavigator` gives "Punktas 3 / 12"
with previous/next, autosave, and shared real-time notes in `AgendaItemNotesSidebar`. The 115 visits
are that flow working. Keep it as a page (deep-linkable from search and notifications). Redesign:
the navigator folds into the title band, notes become the page's local side panel (D1) and a tab or
sheet below `lg`. It is the **reference pattern for sequential editing** — the same idea as O2's
‹ 3 / 24 ›.

**O6. Veikla timeline.** ✅
`CommentViewer` + `ActivityLogSheet` merge into one chronological Veikla section, filterable to
Komentarai / Pakeitimai.

### Creating and editing

**O7. Sheet forms for small entities.** ✅
Tags, types, relationships, event types, resource categories, quick links. Removes ~6 Create/Edit
page pairs.

**O8. Form anatomy.** ✅
No card around the form; inline errors + linked summary; delete out of the save bar; autosave only
for news/page drafts, no switch. The [form rules](rules/pages.md#forms) are settled; rules 15–16 add *composition
vs association* and *reorder mode*; O21 applies them to duty assignments.

**O9. Translations.** ✅ Form-level **LT | EN** switch, per-section missing indicators, per-field peek.

**O10. Merge tools are actions.** ✅ "Sujungti su…" in ⋯ and as a bulk action; in the palette; not
in section tabs.

**O11. Deleting and trash.** ✅ Soft delete → "Ištrinta · Atšaukti" toast; irreversible actions →
confirmation naming the record; "Rodyti ištrintus" filter for users who may restore.

### Attention and communication

**O12. Tasks vs notifications.** ✅ Tasks → Pradžia queue + Užduotys; notifications → bell; tasks
counter becomes a badge on Pradžia / mobile Užduotys; the coordinator tasks summary moves into its
workspace overview.

**O13. Pagalba and onboarding.** ✅ One Pagalba menu (page docs, page tour, report a problem, my
requests, Kas naujo); processing support requests is a Sistema section; one ≤ 5-step welcome tour;
retire sidebar-targeting tours and spotlights; ≤ 1 spotlight per page.

**O14. Paskyra.** ✅ Profilis (public-facing photo and contacts), Pranešimų nustatymai, Išvaizda,
**Mano rolės ir pareigybės** — read-only, listing which workspaces and sections each duty grants.

**O15. Shell extras.** ✅

| Today | Decision |
|---|---|
| START FM player in the sidebar | **Discoverable extra:** account-menu item + palette command "Klausyti START FM" → a small docked player that survives navigation; never autoplays; not in the bottom bar |
| Version + GitHub | Account menu → Apie |
| Followed institutions hotbar | ViSAK overview → "Stebimos institucijos" |
| Pinned pages | Palette star only |
| Recently visited | see O20 |
| Sidebar customise dialog, density | **Dropped** |
| PWA status button | Account menu |
| PWA install / update banners | Keep; mobile only; dismissed once means gone |
| Push permission prompt | Pranešimų nustatymai + contextual after the first notification — never on load |
| Staging / impersonation banners | Full-width ink band above the top bar |
| System announcement | Hairline band under the top bar |

**O16. System feedback states.** ✅
Toasts only for outcomes of the user's own actions; persistent information as an inline band; one
`EmptyState`, one skeleton idiom shaped like content, one navigation progress bar.

### Overviews and data

**O17. Numbers and charts.** ✅ ≤ 3–4 numbers per overview, each linking to its filtered collection;
charts only where the trend is the point, one style (`dataviz`), each with a text summary; Gantt and
timelines are workbenches with a list fallback on mobile.

### Language

**O18. Voice and glossary.** ✅ — see [Copy](rules/visual.md#copy).

### Engineering

**O19. The navigation catalog lives on the server.** ✅
In plain terms: today the list of menu items is written separately in five frontend files
(`AppSidebar`, `ShowAdministration`, `useCommandActions`, `useQuickActions`,
`useActionWindowCatalog`), each repeating its own permission checks. Adding a page means
remembering all five, and they have drifted (see *Navigation drift* in the evidence). Instead, the
list is written **once**, in PHP: "these workspaces, these sections, show this section if the user
may view X". The server sends the browser only the entries this user may see; the tabs, Meniu, the
palette, **+ Sukurti** and Visi skyriai all draw from it. Tests can then assert "a student rep sees
exactly these" and "every admin index page is in the list or deliberately excluded". It reuses
`PermissionMapBuilder`, which already computes the `viewAny`/`create` maps.

**O20. Recently visited.** ✅
- *Today:* a sidebar section + the palette empty state, fed by automatic visit tracking. It lists
  duplicates (the sidebar in the 2026-09-17 screenshot shows "Pareigybių laikotarpiai" twice), every
  entry is stored with the wrong route name, and nobody pins.
- *Settled:* visits are a weak signal. Keep **Neseniai** only in the palette empty state (a
  standard place, zero chrome). On Pradžia show **Neseniai redaguota** — the last 3 records *you
  changed* (activity log, `causedBy`), hidden when empty; it answers "where was I?" better than
  visits do. Ask about it in the live sessions; drop the palette list too if nobody reaches for it.

### People and duties

**O21. One way to put a person in a duty.** ✅
- *Today:* assignments happen in at least five places, each different — `UserForm` (a `TransferList`
  of duties), `DutyForm` (a `TransferList` of people), the four-step `DutyUserWizard`, the
  `EditDutiable` page (dates and details), and the duty timeline's apply/merge.
- *Proposal:* one object, one flow.
  - The object is the **occupancy** (`dutiable`: person × duty × period) — what all of those screens
    edit.
  - One **Priskirti** sheet, opened wherever the coordinator already is, with the known side filled
    in: user record → *Pridėti pareigybę*; duty record → *Pridėti asmenį*; institution record →
    *Pridėti asmenį* on a duty row. Steps: pick the other side → dates (start defaults to today, end
    from the term) → optional details (study programme, email, photo) → save.
  - Each record lists occupancies as rows with **Baigti kadenciją**, **Redaguoti** (the same sheet —
    replaces the `EditDutiable` page) and history.
  - `UserForm` and `DutyForm` lose their `TransferList`s ([Forms](rules/pages.md#forms) rules 1 and 15).
  - The wizard stays as the **bulk term change** (*Kadencijos keitimas*) — an Organizacija tool and a
    coordinator action in **+ Sukurti**. The timeline stays the overview and audit workbench.
  - Palette command: "Priskirti pareigybę".

**O22. Two roles, two names: koordinatorius vs sekretorius.** ✅
- *Today there are two different things, and the plan's first draft confused them:*
  - **Managers** — `Institution::managers()` → `GetInstitutionManagers`, resolved from the role in
    `AtstovavimasSettings::$institution_manager_role_id`, documented as "student rep coordinators who
    can be contacted by student representatives for help/support". This is **exactly** "Tavo
    koordinatorius", already modelled and configured, simply never surfaced to a rep.
  - **Administrators** — the `institution_administrators` pivot, nominated per term, deliberately kept
    out of `users()`/`duties()` because they "carry the institution's tasks and notifications without
    being a member". These are the **technical, secretary-like** nominees: they get the agenda tasks
    and the meeting reminders. Edited inside `InstitutionForm` section 5 and shown only as an avatar
    group, which is why the feature never landed.
- *Settled direction:*
  - **Koordinatorius (manager)** is the human answer to "who do I ask": photo, name, duty and
    **Parašyti**, on Pradžia and on the institution record. No new model — read the settings role.
  - **Sekretorius (administrator)** is the operational nominee: shown on the institution record as
    "Fiksuoja" with the term it applies to, managed **on the record page** (an association, [Forms](rules/pages.md#forms)
    rule 15) rather than inside the form, with a bulk "set for the whole unit for this term" in the
    term settings.
  - Task and reminder emails are *addressed* to the sekretorius and *signed* by the koordinatorius.
- *Settled: **sekretorius**.* The rename (PR 0.5) covers `InstitutionAdministrator` →
  `InstitutionSecretary`, the `institution_administrators` table, `Institution::administrators()` /
  `administratorAssignments()` → `secretaries()` / `secretaryAssignments()`,
  `GetInstitutionAdministrators`, `InstitutionAdministratorController`,
  `UpdateInstitutionAdministratorsRequest`, the `administrators.*` lang keys, `models.d.ts`, the
  `institution-administrators-v1` spotlight key and `docs/`. Do it in one PR, before the message work
  addresses this person (PR 6.3).

**O25. The workspace picker.** ✅
- *Idea:* switch workspaces through something like the public `PadalinysSelector` — a
  mega-menu-style panel of items with descriptions, rather than a row of tabs.
- *Settled: it is the only switcher; there is no tab row.* The trigger names where you
  are (`VISAK ▾`); the panel lists every workspace the person may open with a one-line
  description, its icon and, where cheap, a count ("3 laukia"); the current one is marked and its
  sections are listed beside it, so the panel doubles as *Visi skyriai* on desktop. The second row
  stays the current workspace's section tabs.
- *Why:* it scales from 2 to 6 workspaces without an overflow menu, it gives room for descriptions
  (the thing a rep needs the first three times), it is the same gesture as the public mega menu, and
  it collapses naturally into the mobile Meniu panel — one pattern at every width.
- *Cost:* switching workspaces is one extra click for a power user, which the palette covers (⌘K →
  workspace name). If that proves annoying in the beta, up to three "quick" workspaces can sit beside
  the trigger.
- *Rules:* opens on click **and** on hover (desktop), keyboard-navigable, `Esc` closes; descriptions
  come from the catalog (O19), so they are written once; never hover-only (D-rules: no hover-only
  affordance); marks the current workspace with a brand rule, not a fill.

### Visual system

**O23. A colour system beyond amber.** ✅ — see [Colour system](rules/visual.md#colour-system).

**O24. Pages that don't all look alike.** ✅ — see [Wayfinding and distinctness](rules/visual.md#wayfinding-and-distinctness).

---

## UX practices and enhancements

Round 3, 2026-09-17. "Before" = the phase that needs it.

### State and speed

| # | Status | Practice | Before |
|---|---|---|---|
| U1 | ✅ | **The URL is the state** — filters, sort, view, tab, open sheet/preview (`?item=`); back restores the list position; "Kopijuoti nuorodą" in ⋯ | 5 |
| U2 | ✅ | **Prefetch + instant visits** (Inertia v3) on workspace/section tabs and row links | 4 |
| U3 | ✅ limited | **Keyboard set, kept small:** ⌘K palette, `/` focus collection search, `?` cheatsheet, ⌘↵ save, Esc close. Nothing else until someone asks. | 4 |
| U4 | ✅ | **Optimistic updates** for small state changes (complete task, approve reservation, mark read, publish) with a rollback toast. Pairs with Precognition for forms ([Forms](rules/pages.md#forms) rule 10): optimistic for one-click state changes, Precognition for field validation — they don't overlap. | 5 |
| U5 | ✅ | **Quick actions in collection rows** — in both table and rows views: the one status-like change a person makes most (Publikuoti, Patvirtinti, Pažymėti atliktu) as a button or toggle on the row, optimistic (U4). Never editing text in a row. | 5 |
| U6 | ✅ | **Bulk actions** — one pattern: selection bar pinned to the bottom | 5 |
| U7 | ✅ | **Never lose work** — unsaved-changes guard everywhere; an expired session (419) opens a re-login dialog that keeps the form | 5 |

### Understanding and trust

| # | Status | Practice | Before |
|---|---|---|---|
| U8 | ✅ | **A 403 explains itself** — which permission is missing, who in your unit can grant it, link to Mano rolės | 4 |
| U9 | ✅ | **Dates** — relative when near ("prieš 2 val.", "po 3 d.") with the absolute date in record facts; absolute in tables; Lithuanian formats, Europe/Vilnius; one formatter | 2 |
| U10 | ✅ | **Each status has one name, colour and icon everywhere.** A reservation that is *Rezervuota* reads the same word, with the same colour and icon, in the collection, on the record, in the email and in the requester's notification. One `StatusBadge` + one label map per state enum (e.g. `app/States/ReservationResource`: Created, Reserved, Lent, Returned, Rejected, Cancelled). | 2 |
| U11 | ✅ | **Empty states teach** — what this is, the first action, a docs link; distinct from "no results" | 2 |
| U12 | ✅ | **"Matoma vusa.lt"** — anything shown publicly says so, with a link to the public page | 5 |

### Onboarding and lifecycle

| # | Status | Practice | Before |
|---|---|---|---|
| U13 | ✅ | **First-login checklist** on Pradžia for new reps (nuotrauka, stebimos institucijos, pranešimų nustatymai, pirmas posėdis); disappears when done; replaces the long home tour | 7 |
| U14 | ✅ | **"Tavo prieigos pasikeitė"** — when a duty starts or ends: a notification (bell + email digest), a one-line band on Pradžia until dismissed, and a dated history in Paskyra → Mano rolės ir pareigybės, so it stays findable later | 7 |

### Feedback loop

| # | Status | Practice | Before |
|---|---|---|---|
| U15 | ✅ | **Report a problem with context** — Pagalba pre-fills the page URL, viewport and browser; screenshots later | 4 |
| U16 | ❌ | ~~Beta event tracking~~ — replaced by **live sessions** with reps and outcome metrics from existing data ([Measuring](reps.md#measuring-the-rep-experience)) | — |

### Accessibility and quality

| # | Status | Practice | Before |
|---|---|---|---|
| U17 | ✅ | **Accessibility baseline** — everything keyboard-reachable including drag-and-drop alternatives (move up/down), focus to the page heading after navigation, `aria-live` for toasts and save status, skip link, reduced motion, 4.5:1 text contrast; Storybook a11y `error` on new admin patterns | 2 |
| U18 | ✅ | **Mobile inputs** — per [Pickers and inputs](rules/pages.md#pickers-and-inputs); correct `type`/`inputmode`; `autocomplete` on profile fields | 5 |
| U19 | ✅ | **Performance budget** — Pradžia usable quickly on a mid-range phone; heavy panels deferred; editor, charts and Gantt as lazy chunks, measured on throttled mobile | 4 |
| U20 | ✅ | **Remove the density preference** (`useUIPreferences` density, `data-density` variants in `ui/sidebar`) with sidebar customisation; not reintroduced | 4 |

### Rep-specific (see [Student representatives](reps.md#student-representatives--the-main-group))

| # | Status | Practice | Before |
|---|---|---|---|
| U21 | 🧪 | **Answerable reminders** (R-a) — an experiment on one reminder type (periodicity gap), judged by that task type's completion rate | 7 |
| U22 | ✅ | **Session continuity** (R-b) — browser sessions survive browser close until the idle lifetime ends; retain the forced Microsoft account picker because users commonly hold several accounts; offer local-only logout and optional Microsoft federated logout | 4 |
| U23 | ✅ | **Record now, complete later** (R-c) + paste intake (R-d) + votes as taps (R-e) | 5 |
| U24 | ✅ | **Visible impact and a named coordinator** (R-f, R-g; design in O22) | 7 |
| U25 | ✅ | **Outcome metrics report** — the five metrics, monthly trend | 7 |
| U26 | ✅ | **Coarse device split**, server-side and not Umami: daily login counts per device class + PWA launches, no user id stored | 4 |

### Not doing (for now)

Offline mode · density settings · custom dashboards or widgets · named saved views · per-workspace
themes · a notification-digest redesign · clickstream analytics in the admin.
