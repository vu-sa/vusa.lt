# Component conventions

How shared Vue components are organised, and which one to reach for. Read this
**before creating a new component** — most of what you need already exists.

Related: [Tables](Tables/CLAUDE.md) · [Breadcrumbs](../Composables/BREADCRUMBS_GUIDE.md) · [Frontend testing](../CLAUDE.md) · [Storybook](../../../.storybook/CLAUDE.md)

## Tiers

Dependencies run one way — upward only. A tier never imports from a tier above it.

```
ui/            shadcn-vue primitives. Never edited by hand (regenerated).
  ↑            Never imported directly by Pages/** — ESLint blocks ui/card there.
Patterns/      Generic, domain-free building blocks.
  ↑            SectionCard, EmptyState, EntityLinkCard, DateBadge, ShowPageGrid
<Entity>/      Duties/, Institutions/, Meetings/, Members/, Files/ …
  ↑            Compose Patterns + domain knowledge. One barrel index.ts per folder.
Layouts/       Page shells: AdminContentPage, ShowPageLayout, IndexTablePage, FormUpsertLayout
  ↑
Pages/Admin/   Compose only. No raw <Card>, no hand-rolled hero or grid markup.
```

**Placement test:** does the component call `route()` or reference an
`App.Entities.*` type? Then it is *not* generic — it belongs in an entity folder,
not in `Patterns/`. Pass URLs in as a resolved `href` string instead.

## What do I reach for?

| I need… | Use | From |
|---|---|---|
| An admin **Show** page | `ShowPageLayout` | `@/Components/Layouts/ShowPageLayout.vue` |
| An admin **index** page (table) | `IndexTablePage` | `@/Components/Layouts/IndexTablePage.vue` |
| A **create/edit** form page | `FormUpsertLayout` | `@/Components/Layouts/FormUpsertLayout.vue` |
| Any other admin page shell | `AdminContentPage` | `@/Components/Layouts/AdminContentPage.vue` |
| A titled panel (list, fields, anything) | `SectionCard` | `@/Components/Patterns` |
| Main + sticky sidebar two-column body | `ShowPageGrid` | `@/Components/Patterns` |
| A linked row for one entity (icon, label, chevron) | `EntityLinkCard` | `@/Components/Patterns` |
| A month/day date tile | `DateBadge` | `@/Components/Patterns` |
| "Nothing here yet" | `EmptyState` | `@/Components/Patterns` |
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
`defineModel`. `Pages/Admin/Dashboard/Partials/ReservationKpiStrip.vue` is a good
example of a component that is a control rather than a container.

## ShowPageLayout

Wraps `AdminContentPage` + `ShowPageHero` + `Tabs` + the activity log, and owns
tab persistence. Each entry in `tabs` names the slot that fills it.

```vue
<ShowPageLayout
  :title="duty.name"
  :subtitle="duty.institution?.name"
  :model="duty"
  audit-subject-type="duty"
  :tabs
  tab-storage-key="show-duty-tab"
>
  <template #icon>…</template>
  <template #badge>…</template>
  <template #actions>…</template>
  <template #alert>…</template>

  <template #overview>
    <ShowPageGrid>
      <template #main>…</template>
      <template #sidebar>…</template>
    </ShowPageGrid>
  </template>
</ShowPageLayout>
```

Reserved slot names a tab `value` must not collide with: `icon`, `title`,
`subtitle`, `badge`, `info`, `actions`, `alert`. Omit `tabs` entirely for a
single-body page and use the default slot.

Each tab may carry an optional `icon` (Lucide, rendered before the label) and a
`count` (rendered as a muted suffix; `0` is hidden rather than shown).

By default the layout remembers the open tab under `tabStorageKey`. A page that
needs to *drive* the tab — URL `?tab=` sync, per-entity resets, or another
component navigating to a tab — binds `v-model:tab` instead:

```vue
<ShowPageLayout v-model:tab="currentTab" :tabs>
```

In that controlled mode the layout never touches localStorage, so the page owns
persistence and `tabStorageKey` is ignored. Supply a defined initial value —
that is what marks the layout as controlled. `ShowInstitution.vue` (cross-tab
navigation) and `ShowMeeting.vue` (URL sync) are the reference consumers.

`Pages/Admin/People/ShowDuty.vue` and `ShowUser.vue` are the reference pages.
`Layouts/ShowModel/ShowPageLayout.vue` is the deprecated predecessor — don't use it.
