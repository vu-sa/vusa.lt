# Rules — visual, colour, distinctness, responsive, copy

> **Admin redesign.** Read when: you are writing markup, choosing a colour, or writing user-facing text.
> Index: [README.md](../README.md) · Playbook: [../playbook.md](../playbook.md)

### Visual

1. Page title `u-display` tops out at `text-3xl`/`text-4xl`; section headings sentence case.
2. **Uppercase budget:** eyebrows, workspace tabs, the primary button.
3. **Brand budget:** one brand-filled primary action per region + the active-location marker.
4. **Colour** follows the [Colour system](#colour-system): solid = brand, tinted badge + icon = status,
   small mark = category.
5. Hairlines and whitespace. No shadows, gradients (retire `quickActionGradient`), cards in cards.
6. Minimum 12px readable text (11px only for eyebrows).
7. Empty sections collapse to one line.
8. Photography only where the content owns a photo.
9. Tenant scope is a visible filter, not a hidden global mode.

### Colour system

*Today:* ~1,900 raw Tailwind hue classes across admin pages and components — amber 580, red 342,
emerald 274 **and** green 222, blue 224, orange 65, rose 45, yellow 43, plus indigo, violet, sky, teal,
purple, pink and cyan. None carries a defined meaning.

**Meaning is carried by shape as well as hue:**

| Shape | Means | Example |
|---|---|---|
| **Solid fill** | brand — the primary action, the current location | "Išsaugoti", active tab marker |
| **Tinted badge + icon + label** | status | ✓ Patvirtinta |
| **Small mark** (dot, 20px icon tile, 2px rule) | category — entity type, event type, unit, chart series | ■ Posėdis |

So where hues sit close — light brand burgundy vs danger red, dark brand amber vs attention orange —
the shape still tells them apart, and the icon + label do so without colour at all.

**Six status roles.** Every state in the app maps to one; U10's label maps choose the role.

| Role | Hue | Typical states |
|---|---|---|
| `neutral` | stone | juodraštis, atšaukta, archyvuota, nebalsuota |
| `info` | blue | suplanuota, pateikta, artėja |
| `progress` | violet | vyksta, išduota, peržiūrima |
| `attention` | orange, clearly redder than dark-mode brand amber | laukia veiksmo, artėja terminas, trūksta balsavimo |
| `success` | green | atlikta, patvirtinta, grąžinta, paskelbta, už |
| `danger` | red, brighter and more saturated than the light brand burgundy | vėluoja, atmesta, konfliktas, klaida, prieš |

**Eight categorical hues** (`--cat-1` … `--cat-8`) for entity types, event types, units and chart
series — small marks only, always with a label, never for status.

Rules:
1. Tokens per status role: `--status-{role}` (text/icon), `--status-{role}-surface` (tint),
   `--status-{role}-border`; per category: `--cat-{n}` + `--cat-{n}-surface`. Light and dark values;
   shared by the admin and public surfaces.
2. Contrast: status text ≥ 4.5:1 on its own surface and on the canvas; marks ≥ 3:1 on the canvas.
   Validated by a script (the public revamp's `.design-reference/tokens.mjs` approach), not by eye.
3. Colour-blind safe: never colour alone; success and danger always differ by icon (✓ / ✕).
4. No large coloured surfaces except the ink band — hue lives in small things.
5. Raw hue utilities (`amber-*`, `emerald-*` …) are banned in migrated paths; everything goes
   through `StatusBadge`, category marks or tokens.
6. Charts: one series → foreground + one highlight; several → categorical hues in a fixed order.

#### Concrete tokens (first pass — validate in Phase 2)

`oklch`, light / dark. `-surface` is the badge tint, `-border` its hairline (usually the text colour
at 30–40% alpha).

| Role | Text/icon (light / dark) | Surface (light / dark) |
|---|---|---|
| `neutral` | `.45 .01 80` / `.72 .01 80` | `.93 .005 80` / `.26 .008 80` |
| `info` | `.50 .13 245` / `.78 .10 240` | `.95 .03 245` / `.28 .05 245` |
| `progress` | `.50 .16 300` / `.78 .12 300` | `.95 .035 300` / `.28 .06 300` |
| `attention` | `.55 .16 55` / `.80 .13 60` | `.96 .04 60` / `.30 .07 55` |
| `success` | `.50 .13 150` / `.78 .12 155` | `.95 .035 150` / `.27 .05 150` |
| `danger` | `.52 .19 25` / `.75 .16 25` | `.95 .04 25` / `.28 .07 25` |

Categories `--cat-1…8` at hues **195 · 225 · 265 · 300 · 335 · 45 · 95 · 170**, one lightness pair per
theme (`.52` light / `.76` dark), chroma `.11`. They may share a hue family with a status role because
the *shape* differs (a small mark vs a tinted badge).

#### Where each role may appear

| Role | Allowed in | Never in |
|---|---|---|
| `danger`, `attention` | the attention list, row markers, title-band status, nav/bell counts, task due labels | decorative use; more than one item per row |
| `success` | the object's own status badge, a completed step, a save confirmation | navigation counts (nothing good needs a badge in the nav) |
| `info`, `progress`, `neutral` | anywhere a state must be named | — |
| brand | one solid primary action per region, the current-location marker | any status meaning |
| `--cat-*` | entity-type marks, event types, units, chart series, tags | status meaning |

#### Picking the role

| State | Role |
|---|---|
| The normal, healthy state (active duty, ordinary record) | **no badge at all** — don't paint every row |
| Draft, archived, cancelled, closed, returned, abstained | `neutral` |
| Submitted, scheduled, upcoming, request received | `info` |
| In progress, lent out, under review | `progress` |
| Needs your action, due soon, missing information, no vote recorded | `attention` |
| Done, approved, published, "už", beneficial | `success` |
| Overdue, rejected, failed, conflict, "prieš", harmful | `danger` |

First mappings to write in Phase 2 (one label map per enum, U10):
`app/States/ReservationResource` — Created → `info`, Reserved → `success`, Lent → `progress`,
Returned → `neutral`, Rejected → `danger`, Cancelled → `neutral`.
`VoteValue` — už → `success`, prieš → `danger`, susilaikė → `neutral`, not recorded → `attention`.
Student benefit — naudinga → `success`, nenaudinga → `danger`, neutralu → `neutral`, unknown → `attention`.
Tasks — completed → `success`, open → `info`, due soon → `attention`, overdue → `danger`.
Content — published → `success`, scheduled → `info`, draft → `neutral`.
`SupportRequestStatus`, `InstitutionActivityStatus`, meeting completion → same table.

#### Banned

- Raw hue utilities (`amber-*`, `emerald-*`, `text-red-600` …) anywhere in migrated paths.
- Colour as the only signal — every status badge carries an icon and a word.
- Large coloured surfaces (only the ink band is dark, and it is neutral).
- The brand hue for a status, or a status hue for a category.
- More than one solid brand fill per region.
- `NotificationCategory::color()`'s current list (blue, orange, purple, green, cyan, gray, amber, red,
  indigo, teal) — it collides with the status roles (Task orange, System red, Duty amber). Remap it
  onto `--cat-*` in Phase 2.

#### Validation

- A script resolves every token pair in a real browser and asserts: status text ≥ 4.5:1 on its own
  surface **and** on both canvases; marks ≥ 3:1 on both canvases; no two categorical hues closer than
  ~25° (`.design-reference/tokens.mjs` is the pattern).
- A Storybook swatch story shows all roles and categories in light and dark.
- A snapshot test guards the token list so a rename can't silently drop one.


### Wayfinding and distinctness

A square, hairline, uppercase-eyebrow system makes pages resemble each other. Distinctness comes from
**identity, silhouette and mode** — not decoration, and not per-workspace colours (they would compete
with statuses and categories).

1. **Identity anchor in every title band** — the thing's own visual: person photo, institution logo
   or lettered tile, meeting `DatePlate`, resource photo, document-type tile. Collections show their
   entity's icon tile.
2. **Entity-type marker** — one icon + one categorical colour per entity type, used identically in
   title bands, rows, palette results, notifications, Veikla and breadcrumbs. A meeting looks like a
   meeting everywhere.
3. **Location always stated** — active workspace + section tabs; collection eyebrow
   "ViSAK · POSĖDŽIAI"; record eyebrow = entity type ("POSĖDIS"); breadcrumbs below section
   level; browser tab title "Senato posėdis · Posėdis · VU SA".
4. **Silhouettes by page type**, recognisable before reading:
   - Overview — bands with generous rhythm, the attention list first.
   - Collection — control rows, then a dense list.
   - Record — identity band → key-facts strip → sections.
   - Form — a narrow centred column with a sticky save bar.
   - Workbench — full-bleed; chrome collapses to a thin bar.
5. **Editing is unmistakable** — forms and focused editors sit on the tinted paper canvas
   (`secondary`), with a "REDAGUOJI" eyebrow + the record's name and the sticky save bar. Viewing
   never looks like editing.
6. **At most one ink band per screen, only with meaning** — Pradžia's attention list when not empty,
   a record-level alert (vakansija, konfliktas), impersonation. Rarity keeps it loud.
7. **Content does the differentiating** — photos, logos, date plates and vote bars get room; chrome
   stays constant so content differences read.
8. **Continuity on navigation** — row → record keeps the title in place (a view transition where
   Inertia v3 supports it; check `search-docs` first); off under reduced motion.

### Responsive and touch

1. Design targets **390 · 820 · 1180 · 1440** px, light + dark.
2. Touch targets ≥ 44px via `pointer-coarse:`; desktop tables stay dense.
3. No hover-only information or actions.
4. Dialogs → bottom sheets below `md`.
5. Sticky bottom save bar; filters in a sheet with an active-count badge.

### Copy

1. *Tu* throughout ("Pataisyk klaidas", "Ar tikrai nori ištrinti…").
2. Buttons are verbs naming the result: "Fiksuoti posėdį", "Išsaugoti" — not generic `forms.add`.
3. Lithuanian source keys only.
4. Glossary — **settled 2026-09-17** (counts are occurrences in `resources/js`):

   | Concept | Model | Word | Retired |
   |---|---|---|---|
   | Meeting | `Meeting` | **posėdis** (139) | susitikimas (58) |
   | Event | `Calendar` | **renginys** (144) | įvykis (35) |
   | Person record | `User` | **narys** (105) | vartotojas (8), asmuo |
   | Resource | `Resource` | **išteklius** (37) | resursas (3) |
   | Institution's per-term nominee | `InstitutionAdministrator` | **sekretorius** | administratorius |
   | Institution's contact for reps | via `Institution::managers()` | **koordinatorius** | — |
   | The representation workspace | — | **ViSAK** | Atstovavimas |
   | The tool itself | — | **Mano VU SA** (`mano vusa.lt` when spoken) | VU SA · SISTEMA |

   **ViSAK** = *Virtualus Studentų Atstovų Koordinatorius* — the abbreviation names the
   representation workspace and its tools, so it is a workspace, never the product's name. The
   product keeps its **historic** name, **Mano VU SA** (spoken as "mano vusa.lt"): it is what
   people already call it, and it says "yours" rather than "system". v0's `VU SA · SISTEMA` is
   dropped.

   Three nuances, because the models are broader than their best word:
   - **Not every meeting is a `posėdis`.** The section and the entity are "Posėdžiai" — the word
     everyone uses — and the record names the format from `MeetingType`: in-person / remote → "posėdis"
     (with "Nuotolinis posėdis" as the subtitle), `email` → **"sprendimas el. paštu"**, which is what it
     actually is. So the collective noun stays warm and the record stays accurate.
   - **Not every calendar entry is a `renginys`.** "Renginiai" everywhere in the admin, matching the
     public site; the `EventType` carries anything more specific. "Įvykis" is retired.
   - **`narys` is the warm word and wins**, even though not every person record is a member.
     "Vartotojas" survives **only** in Sistema and in technical contexts where an account (not a
     person) is meant: authentication, roles and permissions, the activity log's causer. Never in
     Organizacija, never in a picker a rep sees.

   The role label "Resursų administratorius" becomes "Išteklių administratorius". Role names are data
   **and** are referenced by string in seeders and tests (`RoleResourceManagerSeeder`, several feature
   tests), so that rename is a data migration plus a grep, not a lang-file edit.
