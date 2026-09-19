# Admin redesign — index

The working plan for redesigning **Mano VU SA** (the admin side of vusa.lt) in the visual language of
the public site. Split into files so a session reads only what it needs.

**Start here, then open only the files your task needs:**

| File | Read when | Lines |
|---|---|---|
| [playbook.md](playbook.md) | migrating or building **any** admin page — the recipe, the decision trees, the CSS traps, the gate | ~90 |
| [rules/pages.md](rules/pages.md) | building a page: page types, collections, records, forms, pickers | ~130 |
| [rules/visual.md](rules/visual.md) | writing markup, picking a colour, writing user-facing text | ~210 |
| [rules/navigation.md](rules/navigation.md) | touching the shell, catalog, palette, or deciding where something lives | ~100 |
| [messages.md](messages.md) | changing a notification, email or push payload | ~85 |
| [reps.md](reps.md) | touching anything a rep uses: Pradžia, meetings, agenda items, tasks, reminders | ~95 |
| [plan.md](plan.md) | picking up work or opening a PR: PR map, phases, open questions, scope | ~355 |
| [process.md](process.md) | starting a session, choosing a model and thinking mode, handing over | ~120 |
| [testing.md](testing.md) | manually verifying phase 0.4 session continuity and Microsoft logout | ~110 |
| [decisions.md](decisions.md) | you need the *why* behind a rule, or think a decision should change | ~355 |
| [evidence.md](evidence.md) | prioritising, or challenging an assumption — role and usage data + the queries | ~70 |

Conventions:

- **✅ means settled.** Don't re-decide it; if it blocks you, stop and add a line to *Open questions*
  in [plan.md](plan.md). 🟡 means still open.
- Every PR has a row in the [PR map](plan.md#pr-map), with its model tier in
  [process.md](process.md#6-which-model-and-in-what-thinking-mode-per-pr).
- **Deprecate transitional code:** any old code, component, route, or wrapper kept before full migration
  must carry an explicit `@deprecated` tag stating what replaces it and when it will be removed.
- Durable rules graduate out of here into `.ai/rules/` via `record-rule`; this folder is deleted in
  the last phase.

Reference assets for the visual language live in `.design-reference/` (public revamp screenshots and
the v0 `/sistema` proposal); the public revamp's own checklist is `REDESIGN.md` at the repo root.

---

## The feel

> **Calm, direct, square.** The public site's paper and ink, VU SA red and amber, hairlines instead
> of boxes — at working density. Every screen answers *what needs me?* before *where can I go?*.
> What you may not do doesn't exist; what you may do is one tab, one ⌘K, or one *Visi skyriai*
> away. It should feel like a well-run office, not a control panel.

### Core concepts — every page follows these

1. **Permissions shape the product.** Workspaces, sections and actions appear on `viewAny` /
   `create`. Hidden, never disabled.
2. **One catalog.** Every destination and action is registered once; every menu reads it.
3. **Attention before navigation.** Tasks come first; empty sections collapse to one line.
4. **Seven page types.** Every page is exactly one of them.
5. **One canonical page per record.** Work objects → record page. Content → its editor.
6. **Forms edit attributes; relations live on the record.**
7. **One create door.** **+ Sukurti** → ActionWindow; guided flows for rare, short tasks.
8. **The palette accelerates, never hides.** Nothing lives only in ⌘K.
9. **Budgets.** One brand fill per region; uppercase only for eyebrows, tabs and the primary button;
   status colours are never brand.
10. **Hairlines and whitespace build structure.** A bordered box means "an object you act on".
11. **Phone and tablet are first-class.** 390 and 820 are design widths; no hover-only anything;
    44px targets on touch.
12. **Speak like a colleague.** *Tu*, verbs on buttons, one glossary.
13. **The five-minute visit is the benchmark.** A rep who taps a link in an email finishes the job
    on that page, on a phone, without learning the app. See [Student representatives](#student-representatives--the-main-group).
