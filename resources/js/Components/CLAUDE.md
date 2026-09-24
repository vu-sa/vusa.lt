# Component conventions

How shared Vue components are organised, and which one to reach for. Read this
**before creating a new component** — most of what you need already exists.

Related: [Tables](Tables/CLAUDE.md) · [Breadcrumbs](../Composables/useBreadcrumbsUnified.ts) · [Frontend testing](../CLAUDE.md) · [Storybook](../../../.storybook/CLAUDE.md)

## Tiers

Dependencies run one way — upward only. A tier never imports from a tier above it.

```
ui/            shadcn-vue wrappers over reka-ui — ours to edit, styled only through tokens.
  ↑            Never imported directly by Pages/** — ESLint blocks ui/card there.
Brand/         DisplayHeading, EyebrowLabel, TagChip — shared by admin and public.
  ↑
Patterns/      Generic, domain-free building blocks.
  ↑            SectionCard, EmptyState, StatusBadge, SheetForm, FormSection, ConfirmDialog …
<Entity>/      Duties/, Institutions/, Meetings/, Members/, Files/ …
  ↑            Compose Patterns + domain knowledge. One barrel index.ts per folder.
Layouts/       Page shells: OverviewPage, CollectionPage, RecordPage, FormPage
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

The two never import each other; what both need lives one tier down, in `Brand/` (the ruled
display headline, the eyebrow, the tag chip) and in `ui/` (buttons and controls). Where both have a
component for the same idea, that is deliberate — `Patterns/DateBadge` is a muted `rounded-lg` inline badge, `Public/Base/DatePlate`
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
| A primary call to action | `ui/button` with `variant="brand" size="lg"` |

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

## Buttons and controls (both surfaces)

- `ui/button` is the one button. Variants are token-only (`brand`, `outline`, `default` ink,
  `ghost`, `secondary`, `destructive`, `link`); the `voice` is bold uppercase by default, which in the admin
  is only for primary actions (`.ai/rules/js-pages-admin.md`). `voice="sentence"` is the admin's sentence-case
  button for everything else — outline, ghost, row actions, chips, Pradžia quick actions: at the default size it is 40px (44px on touch) — don't pass `size="lg"` or a height
  class. `voice="plain"` is for calendar cells and pagination numbers. Sizes: `lg` 48px for a page's primary,
  `default` 44px, `sm` 36px in toolbars and row actions, `icon*` for squares.
- `ui/control` holds the bordered control both sites build filters and toolbar triggers from:
  `controlVariants({ size, active, voice })` (`voice="brand"` uppercase default for public/standard controls; `voice="sentence"` for admin quick filters, facet pills, and toolbar buttons),
  `segmentGroupClass` + `segmentVariants({ active })` for view toggles, `searchFieldClass`,
  `controlCountClass`. Don't hand-roll `border-brand bg-brand/5 …` again.
- `ui/badge` defaults to sentence case / literal casing (`voice="sentence"`), with opt-in `voice="brand"` for uppercase.
- `StatusBadge` renders sentence case by default, automatically keeping `contentStatuses` and `bannerStatuses` uppercase (or via explicit `voice="brand"`).
- Tables style through `ui/table` (hairline box, shaded uppercase header, roomy rows).

## What do I reach for?

| I need… | Use | From |
|---|---|---|
| A workspace **overview** | `OverviewPage` | `@/Components/Layouts/OverviewPage.vue` |
| An admin **collection** (rows / table / preview) | `CollectionPage` + a Typesense, database or local source | `@/Components/Layouts/CollectionPage.vue`, `@/Composables/useCollectionSource` ([Tables/CLAUDE.md](Tables/CLAUDE.md)) |
| An admin **record** page | `RecordPage` | `@/Components/Layouts/RecordPage.vue` |
| A **create/edit** form page | `FormPage` | `@/Components/Layouts/FormPage.vue` |
| A titled group of fields inside a form | `FormSection` | `@/Components/Patterns` |
| A settings box in a form's `#aside` (+ switch rows) | `FormPanel`, `FormToggleRow` | `@/Components/Patterns` |
| A small create/edit over its collection or record | `SheetForm` | `@/Components/Patterns` |
| "Are you sure?" before ending, discarding or deleting | `ConfirmDialog` | `@/Components/Patterns` |
| A status (reservation, vote, task, content…) | `StatusBadge` + the enum's map in `Constants/statuses.ts` | `@/Components/Patterns` |
| An entity type's icon + category colour | `EntityTypeMark` / `getEntityTypeDefinition` | `@/Components/EntityTypeMark.vue`, `@/Constants/entityTypes` |
| A settings screen with several independent saves | `OverviewPage` + a `SectionCard` per block | `@/Components/Layouts/OverviewPage.vue`, `@/Components/Patterns` |
| A titled panel (list, fields, anything) | `SectionCard` | `@/Components/Patterns` |
| Main + sticky sidebar two-column body | `ShowPageGrid` | `@/Components/Patterns` |
| A linked row for one entity (icon, label, chevron) | `EntityLinkCard` | `@/Components/Patterns` |
| A ruled grid of destinations (icon, label, description) — Visi skyriai, overview sections, quick access | `NavigationTiles` (`WorkspaceSectionTiles` for a workspace's catalog sections) | `@/Components/Patterns`, `@/Components/Overview` |
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

`FormPage` is the shell for a form that edits one record's own attributes (`.ai/rules/admin-forms.md`), on the
tinted edit canvas with a "Redaguoji" / "Kuri naują" eyebrow, a `u-display` title and lead. While it is mounted
the shell is in **focus mode** (`useShellFocus`): the workspace picker, palette, section tabs, breadcrumbs and
bottom nav give way to the form's own bar (back, save state, `#header-actions`, Išsaugoti), teleported into
`ShellTopBar`; below `md` Išsaugoti sits in a bottom save bar instead. Pass an `#aside` slot for the two-column
v0 shape: fields in the main column, settings in `FormPanel`s (`Patterns/`, with `FormToggleRow` for switch
rows) — `PageForm.vue` and `NewsForm.vue` are the reference. The layout supplies the editor chrome from props:
`public-url` (Peržiūrėti viešai), `activity-subject` (change history), `created-at` / `updated-at` (facts at the
end of the aside); in the two-column shape `#danger-zone` closes the aside. Content editors share
`AdminForms/ContentPublishPanel` (status, publish time, visibility callout), `ContentLanguagePanel` (language +
other-language link) and `TenantSelectField`; choices of 2–5 options use `Patterns/FormSegmentedControl`, and
fields on the tinted canvas take `fieldSurfaceClass` from `ui/control`. Without an aside the form stays one
column (`#advanced` "Papildomi nustatymai", `#danger-zone`). It owns three behaviours callers should not re-implement: **⌘/Ctrl + Enter**
submits and **Esc** cancels (listeners on the `<form>`, so a portaled Select never cancels it), and on a failed
submit the error summary is scrolled into view and focused, each message focusing its field. Pass
`mode="create"` for a new record (the bar never claims "all saved"), `field-ids` when an error key is not the
field's id (`name.lt` → `duty-name`), and `:available-locales="[]"` for a single-language record. Field labels
and hints come from `FormFieldWrapper` (uppercase micro-label, hint under the field — never a tooltip).
Relations with their own lifecycle do **not** belong in it.

`SheetForm` is the same idea over a collection or record: a right sheet, a bottom sheet below `md`, a
`#danger-zone` in the body (never the footer), and a `dirty` prop that makes Esc, the overlay and
Atšaukti ask before discarding. `FormSection` groups fields under a question heading with an optional
"Matoma vusa.lt" marker. `ConfirmDialog` is the one confirmation: name the result on the button
("Baigti kadenciją", "Ištrinti"), never a native `confirm()`.

## RecordPage

`RecordPage` is the canonical shell for migrated records. It owns the identity/title band,
status and action placement, key facts, desktop tabs/mobile stacked sections, collection-context
navigation, and the final `activity` slot. Pass resolved URLs and permission-filtered actions;
domain behavior stays in the page.

Collections: see [Tables/CLAUDE.md](Tables/CLAUDE.md).
