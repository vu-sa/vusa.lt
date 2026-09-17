# Rules — page types, collections, records, forms, pickers

> **Admin redesign.** Read when: you are building or migrating a page.
> Index: [README.md](../README.md) · Playbook: [../playbook.md](../playbook.md)

### Page types

| Type | Anatomy | Mobile |
|---|---|---|
| Overview | needs attention → coming up → sections | attention + **+** in first viewport |
| Collection | see [Collections](#collections) | rows; filters in a sheet |
| Record | title band (type eyebrow, title, status, primary action, ⋯) → key facts → sections → Veikla | sections stack; ⋯ sheet |
| Form | see [Forms](#forms) | sticky save bar |
| Sheet form | small entity over its collection | full-height sheet |
| Guided flow | ActionWindow, one question per screen | bottom sheet |
| Workbench | full-bleed, minimal chrome, may have a local side panel | read-only or simplified |

### Collections

`resources/js/Pages/Public/ShowDocuments.vue` is the reference anatomy.

1. **Title band** — workspace eyebrow, title, one-line lead, primary create.
2. **Quick filters** — the most-used values as one-click chips (documents: "Svarbiausi" types;
   meetings could be "Mano institucijos", "Be balsavimų", "Šie metai").
3. **Control row** — search · **Filtrai** (active-count badge) · **Rikiuoti** · view toggle
   (**Eilutės · Lentelė · Peržiūra**; rows only below `md`, preview from `xl`).
4. **Filter row** — filter popovers, toggled by Filtrai; a bottom sheet below `md`. Tenant scope is
   one of them.
5. **Active chips** + "Išvalyti visus" · "Rasta N".
6. **Results** — rows or table (O1); optional preview pane on ≥ `xl` for queues (O2).
7. **More** — "Rodyti daugiau", 50 at a time; back navigation restores loaded rows and scroll; the
   URL carries filters, sort and view.
8. **Empty** — first use (what this is + create + docs link) differs from no results (clear filters).
9. **Selection** — checkbox column in table view; a "Pasirinkti" mode in rows view; a selection bar
   pinned to the bottom (`ReservationBulkActionBar` precedent).

### Records

1. Title band: type eyebrow, title, status, **one** primary action, ⋯ for the rest (edit, merge,
   delete, copy link).
2. Key facts strip — the 3–6 attributes people look up most.
3. Sections (tabs on desktop when ≥ 3 substantial sections; stacked on mobile).
4. Relations are managed here, not in the form.
5. **Veikla** closes the page: comments + changes, filterable.
6. Opened from a filtered collection → **‹ 3 / 24 ›** in the title band.

### Forms

1. **A form edits the record's own attributes.** Relations with their own lifecycle (duty members,
   roles, agenda items, files) are managed on the record page. `DutyForm`'s members `TransferList`
   is the example to move.
2. **Create asks only what the record needs to exist**; the rest is edited afterwards.
3. **Sections are questions** — "Kas tai?", "Kur tai rodoma?", "Kas gali redaguoti?" — at most
   ~6 fields each. More → split, or collapse rare fields under **Papildomi nustatymai**.
4. **Single column**, ~40rem field measure. Two fields side by side only when they are a pair
   (pradžia/pabaiga, vardas/pavardė).
5. **Where text goes:**

   | Kind of text | Place | Length |
   |---|---|---|
   | What the field is | Label | 1–4 words |
   | How to fill it / what happens | Hint under the field | 1 line |
   | Why the section matters / where it shows | Intro under the section title | 1 sentence |
   | Background, policy, examples | **Plačiau** disclosure or docs link | any |
   | A consequence that applies *now* (duplicate, access change, public visibility) | Callout at the triggering field, only while it applies (`DuplicateDutyWarning` precedent) | 1–2 lines |

   Never: a placeholder as the only hint; paragraphs in a side column; the same explanation twice.
6. **Section intros sit above the fields at every width.** Retire `FormElement`'s left description
   column — it grows into essays and becomes a different layout on mobile.
7. **Mark the minority.** Mostly required → mark optional fields "(neprivaloma)". No "Privaloma"
   section badges.
8. **Controls fit the choice.** 2–5 options → visible radio/segmented; more → select or combobox;
   records → search picker; a boolean that applies immediately → switch; one saved with the form →
   checkbox.
9. **Sensible defaults** — tenant, dates, the previous agenda item's end time (already done).
10. **Validation** — format on blur, the rest on submit; summary at the top linking to fields;
    focus the first error. **Use Laravel Precognition for server-only rules** (unique permalink,
    tenant scope, date conflicts) — `useForm(...).withPrecognition()` + `form.validate('field')` on
    change, as `NewsForm`, `PageForm` and `CalendarForm` already do. One source of rules (the Form
    Request), no duplicated client validation. Validate on `change`/blur, never per keystroke.
11. **No completion checkmarks or numbered sections** on ordinary forms; only in multi-step wizards.
12. **Public marker** — fields and sections shown on vusa.lt carry "Matoma vusa.lt" (U12).
13. Save bar: status + **Išsaugoti**; delete lives in ⋯ or a final "Pavojinga zona"; no autosave switch.
14. Translations: form-level **LT | EN** switch, per-section "EN: trūksta N laukų", per-field peek (O9).
15. **Composition vs association** — refines rule 1:
    - *Composition*: parts that only exist inside their parent and are authored with it (a form's
      fields, rich-content blocks, navigation items, a vote's options) → edited **in the parent's
      editor or workbench**, order included.
    - *Association*: links between things with their own life (person ↔ duty, duty ↔ institution,
      institution ↔ administrators, user ↔ roles) → managed **on a record page**, never in a form.
16. **Order is a mode, not always-on drag.** A section whose order matters (agenda items, duties
    within an institution, form fields, quick links, navigation) has **Keisti tvarką**: drag
    handles plus ↑/↓ buttons (keyboard and phone — drag is unreliable on touch), then
    **Išsaugoti tvarką** / **Atšaukti**. Order shown on vusa.lt carries "Matoma vusa.lt".
    `MeetingAgendaList`'s editing switch and `SortableDutiesTable` are the starting points.

### Pickers and inputs

Principle: **native where the operating system does it better** (touch, accessibility, locale, zero
maintenance); **custom where the data needs it** (search, rich rows, many values, records).

| Need | Fine pointer (desktop) | Coarse pointer (phone/tablet) |
|---|---|---|
| A date usually near today (meeting, reservation) | **Preset chips first** (Šiandien · Vakar · Kita data…), then the date field | same — presets matter most here (ActionWindow `MeetingWhenScreen` precedent) |
| Any date | Typed field (`2026-09-17`) + calendar popover; typing always works | native `<input type="date">` |
| Time | Typed `hh:mm` + 15-minute suggestions | native `<input type="time">` |
| Date + time | **Two fields**, date and time | two native fields — never a combined popover |
| Date range | Two date fields; end defaults to start | two native fields; a range calendar only where occupancy matters (reservations) |
| Count (0–20) | `NumberField` with steppers | same, 44px steppers |
| Year, ID, large number | text input, `inputmode="numeric"` | same |
| One of 2–5 options | Visible radio / segmented (`ToggleGroup`) | same |
| One of 6–15 plain-text options | Styled **native `<select>`** | native `<select>` (OS wheel/list) |
| Options that need icons, descriptions or colour | shadcn `Select` | shadcn `Select` rendered as a bottom sheet |
| One of many (16+), not search-indexed | `SingleSelect` (searchable combobox) | full-screen sheet: search field on top, 44px rows |
| Records from an indexed collection | Search picker (`CollectionSelectDialog`; inline Typesense list in the ActionWindow) | full-screen sheet, one level deep |
| Several of ≤ 10 | Visible checkboxes | same |
| Several of many | `MultiSelect` with removable chips | sheet with search + checked rows; chips in the field |
| People assigned to a thing | "Pridėti asmenį" picker + list with remove actions **on the record page** | same — retire `TransferList` (two panes do not fit a phone) |
| Tags | `TagMultiSelect` | same |
| Image / file | Drop zone + "Pasirinkti iš failų" | file input with `accept` (phones offer camera or files) |

Rules:
1. **One picker per data kind** across the admin — no second date picker.
2. The trigger shows the value as readable text plus context (institution + unit chip), never an id.
3. Optional fields have a visible "Išvalyti".
4. Lithuanian locale everywhere: Monday first, 24-hour time, Lithuanian month names.
5. When the user may create the missing thing, the picker offers "Sukurti naują" in a sheet and
   returns with it selected — not a link opening a new tab (`DutyForm` does that today).
6. No dialog inside a dialog: on mobile a picker opened from a sheet replaces the sheet's content
   (with back) instead of stacking.
7. This table extends `.ai/rules/single-select.md` (plain `Select` → `SingleSelect` →
   `CollectionSelectDialog`); record the merged version with `record-rule` in Phase 10.
