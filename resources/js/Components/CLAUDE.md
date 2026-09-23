# Component conventions

How shared Vue components are organised, and which one to reach for. Read this
**before creating a new component** — most of what you need already exists.

Related: [Tables](Tables/CLAUDE.md) · [Breadcrumbs](../Composables/useBreadcrumbsUnified.ts) · [Frontend testing](../CLAUDE.md) · [Storybook](../../../.storybook/CLAUDE.md)

## Tiers

Dependencies run one way — upward only. A tier never imports from a tier above it.

```
ui/            shadcn-vue primitives. Never edited by hand (regenerated).
  ↑            Never imported directly by Pages/** — ESLint blocks ui/card there.
Patterns/      Generic, domain-free building blocks.
  ↑            SectionCard, EmptyState, StatusBadge, SheetForm, FormSection, ConfirmDialog …
<Entity>/      Duties/, Institutions/, Meetings/, Members/, Files/ …
  ↑            Compose Patterns + domain knowledge. One barrel index.ts per folder.
Layouts/       Page shells: OverviewPage, CollectionPage, RecordPage, FormPage
               (legacy: AdminContentPage, IndexTablePage, FormUpsertLayout)
  ↑
Pages/Admin/   Compose only. No raw <Card>, no hand-rolled hero or grid markup.
```

**Placement test:** does the component call `route()` or reference an
`App.Entities.*` type? Then it is *not* generic — it belongs in an entity folder,
not in `Patterns/`. Pass URLs in as a resolved `href` string instead.

## Two tiers, two surfaces

`Patterns/` is **admin-only** by construction — its barrel says so, and nothing under
`Pages/Public/**` imports it. The public site has its own primitives tier and its own visual
language (sharp corners, hairline rules, one brand accent per view):

```
ui/  →  Public/Base/  →  Public/<area>/  →  Public/Layouts/  →  Pages/Public/
ui/  →  Patterns/     →  <Entity>/       →  Layouts/         →  Pages/Admin/
```

The two never import each other. Where both have a component for the same idea, that is
deliberate — `Patterns/DateBadge` is a muted `rounded-lg` inline badge, `Public/Base/DatePlate`
is a square plate with a brand rule sized to sit on a photograph.

### `Components/Public/Base/`

| I need… | Use |
|---|---|
| A page section (measure + vertical rhythm) | `SectionBand` |
| A ruled headline block (eyebrow + title + lead) | `DisplayHeading` |
| The band a listing/detail page opens with | `PageTitleBand` |
| A brand-coloured kicker on its own | `EyebrowLabel` |
| A list of things (instead of cards) | `HairlineList` + `HairlineRow` |
| A day/month plate over an event image | `DatePlate` |
| A fixed-ratio, grayscale image | `MediaFrame` |
| A category marker | `TagChip` |
| One figure in a stats strip | `StatCell` |
| Reader preferences (text size, contrast, underlines) | `AccessibilityMenu` |
| The site mark | `HeaderWordmark` |
| A primary call to action | `ui/button` with `variant="brand"` |

Rules for anything added there:

- **Colour comes from tokens** — `bg-card`, `text-muted-foreground`, `border-border`,
  `text-brand`. A hardcoded `bg-white dark:bg-zinc-900` cannot follow the surface, which is the
  whole point of the tier.
- **`--brand`, not `--accent`.** `--accent` is shadcn's hover/muted surface (`hover:bg-accent` on
  ghost buttons, `focus:bg-accent` on dropdown items). The VU SA brand — red on light, amber on
  dark — is `text-brand` / `bg-brand-fill` / `text-brand-foreground`.
- **No `rounded-*`.** The public surface zeroes the radius scale, but `rounded-full` is a literal
  and would survive it.
- **Domain-free**, same as `Patterns/`: no `route()`, no `App.Entities.*`. Pass a resolved `href`.
- **Every primitive ships a story** covering its variants, with `parameters: { a11y: { test:
  'error' } }`. Storybook is the only place the rendered result can be checked in both themes —
  jsdom cannot resolve Tailwind's `dark:` variant.

## What do I reach for?

| I need… | Use | From |
|---|---|---|
| A workspace **overview** | `OverviewPage` | `@/Components/Layouts/OverviewPage.vue` |
| An admin **collection** (rows / table / preview) | `CollectionPage` + `useDatabaseCollectionSource` / `useTypesenseCollectionSource` | `@/Components/Layouts/CollectionPage.vue`, `@/Composables/useCollectionSource` |
| An admin **record** page | `RecordPage` | `@/Components/Layouts/RecordPage.vue` |
| A **create/edit** form page | `FormPage` | `@/Components/Layouts/FormPage.vue` |
| A titled group of fields inside a form | `FormSection` | `@/Components/Patterns` |
| A small create/edit over its collection or record | `SheetForm` | `@/Components/Patterns` |
| "Are you sure?" before ending, discarding or deleting | `ConfirmDialog` | `@/Components/Patterns` |
| A status (reservation, vote, task, content…) | `StatusBadge` + the enum's map in `Constants/statuses.ts` | `@/Components/Patterns` |
| An entity type's icon + category colour | `EntityTypeMark` / `getEntityTypeDefinition` | `@/Components/EntityTypeMark.vue`, `@/Constants/entityTypes` |
| A settings screen with several independent saves | `AdminContentPage` + a `SectionCard` per block (no page type fits yet) | `@/Components/Layouts/AdminContentPage.vue` |
| A titled panel (list, fields, anything) | `SectionCard` | `@/Components/Patterns` |
| Main + sticky sidebar two-column body | `ShowPageGrid` | `@/Components/Patterns` |
| A linked row for one entity (icon, label, chevron) | `EntityLinkCard` | `@/Components/Patterns` |
| A month/day date tile | `DateBadge` | `@/Components/Patterns` |
| "Nothing here yet" / Filter empty | `EmptyState` | `@/Components/Patterns` |
| Loading placeholder shaped like content | `CollectionSkeleton`, `RecordSkeleton`, `FormSkeleton`, `SectionCardSkeleton` | `@/Components/Patterns` |
| Hairline top loading bar for panels/tables | `TopProgressBar` | `@/Components/Patterns` |
| Documents for any model | `FileablePreviewCard` | `@/Components/Files` |
| A clickable card's hover styling | `interactiveCardClass` | `@/Utils/interactiveCard` |

Do **not** hand-roll `<Card><CardHeader><CardTitle>` in a page. That is
`SectionCard`, which already handles the title, icon, count badge, action
link/button, empty state and footer.

## Before adding a new card component

There are already ~40 card components. Check for an existing one first:

```bash
find resources/js -name '*Card*.vue' -not -path '*/ui/*'
```

Then, in order of preference:

1. Can `SectionCard` or `EntityLinkCard` do it with different props? Use it.
2. Is it the same entity as an existing card? Extend that card with a prop or slot.
3. Genuinely new *and* domain-free? Add to `Patterns/` and export from its barrel.
4. Genuinely new and entity-specific? Add to the entity folder and its barrel.

Page-local components (`Pages/Admin/<Domain>/Components/`) are a last resort, for
things that truly cannot be reused. `Dashboard/` is the only folder doing this today.

## Writing a shared component

`Hero/ShowPageHero.vue` is the reference implementation. Match its shape:

- Props **and** a matching slot for anything a caller might need to override
  (`:title` prop with a `#title` slot that wins).
- Merge incoming classes with `cn()` from `@/Utils/Shadcn/utils` and accept
  `class?: HTMLAttributes['class']`.
- Add a `data-slot="kebab-name"` on the root for styling and test hooks.
- Type-based `defineProps` / `defineEmits` (enforced by ESLint).
- Every colour needs its `dark:` counterpart, or use semantic tokens
  (`bg-card`, `text-muted-foreground`, `border-border`) which already adapt.
- Comment *why* a non-obvious class or branch exists, not what it does.

State belongs in the page; components communicate upward via typed emits or
`defineModel`. `Components/Overview/OverviewScopeSwitch.vue` is a good
example of a component that is a control rather than a container.

## FormPage, SheetForm, FormSection

`FormPage` is the shell for a form that edits one record's own attributes (`.ai/rules/admin-forms.md`): a
narrow column on the tinted edit canvas with a "Redaguoji" / "Kuri naują" eyebrow, an optional LT | EN
switch, a `#advanced` disclosure ("Papildomi nustatymai"), a `#danger-zone`, and a sticky save bar. It
owns three behaviours callers should not re-implement: **⌘/Ctrl + Enter** submits and **Esc** cancels
(listeners on the `<form>`, so a portaled Select never cancels it), and on a failed submit the error
summary is scrolled into view and focused, each message focusing its field. Pass `mode="create"` for a new
record (the save bar never claims "all saved"), and `field-ids` when an error key is not the field's id
(`name.lt` → `duty-name`). Relations with their own lifecycle do **not** belong in it.

`SheetForm` is the same idea over a collection or record: a right sheet, a bottom sheet below `md`, a
`#danger-zone` in the body (never the footer), and a `dirty` prop that makes Esc, the overlay and
Atšaukti ask before discarding. `FormSection` groups fields under a question heading with an optional
"Matoma vusa.lt" marker. `ConfirmDialog` is the one confirmation: name the result on the button
("Baigti kadenciją", "Ištrinti"), never a native `confirm()`.

`Layouts/FormUpsertLayout` is `@deprecated`; new forms use `FormPage`.

## RecordPage

`RecordPage` is the canonical shell for migrated records. It owns the identity/title band,
status and action placement, key facts, desktop tabs/mobile stacked sections, collection-context
navigation, and the final `activity` slot. Pass resolved URLs and permission-filtered actions;
domain behavior stays in the page.

`IndexTablePage` and `AdminContentPage` remain only for pages not yet migrated; don't add new
consumers (see `.ai/rules/js-pages-admin.md` → migrate on touch).
