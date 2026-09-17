# Migration playbook

> **Admin redesign.** Read when: you are migrating or building any admin page.
> Index: [README.md](README.md) · Rules: [rules/pages.md](rules/pages.md), [rules/visual.md](rules/visual.md), [rules/navigation.md](rules/navigation.md)

## Migration playbook

### Per-page recipe

```
 1. Classify  → page type (tree below)
 2. Place     → catalog entry: workspace, section, gate — or "reached from record X"
 3. Strip     → card-around-everything, gradients, shadows, rounded-*, raw zinc/gray/white, <12px text
 4. Rebuild   → the page type's layout; ONE primary action; hairline structure
 5. Move      → relations out of forms onto the record; delete into ⋯; rare fields into "Papildomi nustatymai"
 6. Copy      → tu, verbs, glossary terms, Lithuanian source keys, text placement rules
 7. States    → empty (teaches), loading (skeleton shaped like the content), error, no permission
 8. Check     → 390 · 820 · 1180 · 1440, light + dark, touch (no hover-only), keyboard only
 9. Tests     → behaviour contracts that changed (visibility gates, redirects, deferred props)
10. Retire    → tours/spotlights pointing at old markup; update the docs/ page
11. Tick      → the phase checklist; surprises into Notes
```

### Decision trees

**Which page type?**
- Starts a day or a workspace → **Overview**
- Many of one thing → **Collection**
- One thing with relations, status or discussion → **Record**
- The thing *is* its content (news, page) → **Form** (the editor is canonical)
- ≤ ~4 fields, no relations worth a page → **Sheet form**
- An occasional user's task with ≤ ~6 decisions → **Guided flow**
- A canvas (content editor, Gantt, graph, navigation builder) → **Workbench**

**Rows or table?** Scanning to find an item → rows. Comparing attributes, sorting by column, bulk
work → table. Every collection offers all views; this only picks the **default**, and the user's
choice is remembered.

**Preview pane?** Only when people *process a queue* — decide on one item, move to the next.
Otherwise a row opens the record.

**Dialog, sheet or page?** Confirming → dialog. Small create/edit or a preview → sheet (bottom sheet
below `md`). Worth a shareable URL or longer than one screen → page.

**Where does this text go?** → [Form rules](rules/pages.md#forms), rule 5.

**Does it need a spotlight?** Only if a returning user would look for it in its old place. At most
one visible per page; bump `-v<n>` when it changes again.

---

## Traps carried over from the public revamp

Re-read `REDESIGN.md` → *The two traps* and the Phase 3/4 notes.

1. **`--accent` is shadcn's hover surface, not brand.** Brand is `--brand` / `--brand-fill`.
2. **`@theme inline` breaks scoped radius overrides** — the radius scale stays in plain `@theme`.
3. **A `dark:`-prefixed class beats an unprefixed one regardless of order** — every consumer
   override of a shadcn variant needs its `dark:` twin.
4. **`rounded-full` survives a zeroed radius scale** (avatars, switches, badges).
5. **`ui/button` force-squares any descendant `<svg>` without a `size-*` class.**
6. **`variant="outline"` hardcodes `bg-white`.**
7. **Renderless reka roots** (`Popover`) swallow fallthrough classes — wrap in an element.

---

## Per-phase gate

```bash
vendor/bin/sail npm run test
vendor/bin/sail npm run test:storybook
vendor/bin/sail artisan test --parallel --compact
vendor/bin/sail npm run lint:file -- <changed files>
```

Manual pass, via impersonation:

| Persona | Holds |
|---|---|
| Rep | Studentų atstovas only |
| Rep + comms | Studentų atstovas + Komunikacijos koordinatorius |
| Resources | Resursų administratorius |
| Super admin | everything |

At 390 · 820 · 1180 · 1440, light + dark, once on a real touch device, once keyboard-only.
Screenshots at the four widths (light + dark) go with the PR (`.design-reference/*.mjs`).
