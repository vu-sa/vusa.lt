<template>
  <div class="rounded-lg border bg-background">
    <div class="flex items-center gap-2 border-b p-3">
      <button type="button" class="nav-root-handle cursor-grab touch-none text-zinc-400 hover:text-zinc-600 active:cursor-grabbing dark:hover:text-zinc-300">
        <GripVertical class="size-4" />
      </button>
      <span class="text-base font-semibold" :class="[!root.is_active && 'opacity-60']">{{ root.name || `#${root.id}` }}</span>
      <Badge v-if="!root.is_active" variant="secondary" size="tiny">
        {{ $t('navigation.builder.inactive') }}
      </Badge>

      <div class="ml-auto flex items-center gap-1">
        <Tooltip>
          <TooltipTrigger as-child>
            <!-- See NavigationLinkCard.vue for why the Switch needs this wrapper:
                 TooltipTrigger's `as-child` merges its own `data-state` onto whatever
                 single element it wraps, which would otherwise clobber the Switch's
                 own `data-state` (checked/unchecked) and blank out its color classes. -->
            <span class="inline-flex">
              <Switch
                :model-value="root.is_active"
                @update:model-value="val => $emit('toggle-active', root, val)"
              />
            </span>
          </TooltipTrigger>
          <TooltipContent>
            {{ root.is_active ? $t('navigation.builder.active') : $t('navigation.builder.inactive') }}
          </TooltipContent>
        </Tooltip>
        <Link :href="route('navigation.edit', { navigation: root.id })">
          <Button size="icon-xs" variant="ghost">
            <Pencil class="size-3.5" />
          </Button>
        </Link>
        <AlertDialog>
          <AlertDialogTrigger as-child>
            <Button size="icon-xs" variant="ghost" class="text-destructive hover:text-destructive">
              <Trash2 class="size-3.5" />
            </Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>{{ $t('navigation.builder.delete_confirm_title') }}</AlertDialogTitle>
              <AlertDialogDescription>{{ $t('navigation.builder.delete_confirm_description') }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>{{ $t('forms.cancel') }}</AlertDialogCancel>
              <AlertDialogAction @click="$emit('delete', root)">
                {{ $t('forms.delete') }}
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>
    </div>

    <div class="grid gap-3 p-3 lg:grid-cols-3" :class="[!root.is_active && 'opacity-60']">
      <NavigationColumn
        v-for="(column, index) in root.links"
        :key="index"
        :ref="(instance) => setColumnRef(instance, index)"
        :links="column"
        :index="index"
        @toggle-active="(link, val) => $emit('toggle-link-active', link, val)"
        @delete="link => $emit('delete-link', link)"
      />
    </div>

    <div class="border-t p-2">
      <Button :as="Link" variant="ghost" size="sm" class="gap-1.5" :href="route('navigation.create', { parent_id: root.id })">
        <Plus class="size-3.5" />
        {{ $t('navigation.builder.add_link') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, shallowRef, unref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link } from '@inertiajs/vue3';
import { insertNodeAt, removeNode, useSortable } from '@vueuse/integrations/useSortable';
import { GripVertical, Pencil, Plus, Trash2 } from 'lucide-vue-next';

import NavigationColumn from './NavigationColumn.vue';

import type { AdminNavigationLink, AdminNavigationRoot } from './types';

import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';

const props = defineProps<{
  root: AdminNavigationRoot;
}>();

defineEmits<{
  (event: 'toggle-link-active', link: AdminNavigationLink, value: boolean): void;
  (event: 'delete-link', link: AdminNavigationLink): void;
  (event: 'toggle-active', root: AdminNavigationRoot, value: boolean): void;
  (event: 'delete', root: AdminNavigationRoot): void;
}>();

// `@vueuse/integrations`' useSortable only wires SortableJS's `onUpdate` (same-list
// reorder) — it has no built-in support for moving an item between two *different*
// lists (see its source: the only default option is `onUpdate: moveArrayElement`).
// A cross-column drag needs a handler that can see and splice both of a root's
// columns at once, which one column's own isolated component instance can't do —
// hence registering all 3 columns' Sortable instances here instead of inside
// NavigationColumn itself.
//
// `links` is always exactly 3 column arrays (see types.ts), so 3 stable refs cover
// every root without needing a dynamic ref array.
type ColumnInstance = { el?: HTMLElement | null } | null;
const columnEls = [
  shallowRef<HTMLElement | null>(null),
  shallowRef<HTMLElement | null>(null),
  shallowRef<HTMLElement | null>(null),
];

const setColumnRef = (instance: unknown, index: number) => {
  const typed = instance as ColumnInstance;
  columnEls[index].value = typed ? (unref(typed.el) ?? null) : null;
};

/** The subset of SortableJS's `onEnd` event payload this handler reads. `sortablejs`
 * ships no type declarations of its own (no @types package either), so this is a
 * minimal local shape rather than an unresolvable import. */
interface SortEndEvent {
  item: HTMLElement;
  from: HTMLElement;
  to: HTMLElement;
  oldIndex?: number;
  newIndex?: number;
}

/** Same rationale as SortEndEvent — the subset of SortableJS's `onMove` payload used
 * to decide whether a drop is allowed. */
interface SortMoveEvent {
  dragged: HTMLElement;
  from: HTMLElement;
  to: HTMLElement;
}

function isFullHeightLink(link: AdminNavigationLink | undefined): boolean {
  return link?.extra_attributes?.type === 'full-height-background-link';
}

function findLinkById(id: number): AdminNavigationLink | undefined {
  for (const column of props.root.links) {
    const found = column.find(l => l.id === id);
    if (found) {
      return found;
    }
  }
  return undefined;
}

// A full-height-background-link fills its whole column on the public site (see
// NavigationLinkCard.vue), so it may only ever be the sole occupant of one: dropping
// it is allowed into an empty column, or back into its own (a same-column reorder is
// a no-op there since it's already alone). Conversely, no other link may join a
// column that already holds one — that column is reserved for it.
function handleSortMove(evt: SortMoveEvent): boolean {
  if (evt.to === evt.from) {
    return true;
  }

  const toIndex = columnEls.findIndex(elRef => elRef.value === evt.to);
  if (toIndex === -1) {
    return true;
  }

  const targetColumn = props.root.links[toIndex];
  const draggedLink = findLinkById(Number(evt.dragged.dataset.linkId));

  if (isFullHeightLink(draggedLink)) {
    return targetColumn.length === 0;
  }

  return !targetColumn.some(isFullHeightLink);
}

function handleSortEnd(evt: SortEndEvent) {
  const fromIndex = columnEls.findIndex(elRef => elRef.value === evt.from);
  const toIndex = columnEls.findIndex(elRef => elRef.value === evt.to);

  if (fromIndex === -1 || toIndex === -1 || evt.oldIndex == null || evt.newIndex == null) {
    return;
  }

  // SortableJS already moved the real DOM node as the user dragged. Revert that
  // (mirroring vueuse's own moveArrayElement) so Vue's reactive re-render — driven
  // by the array splices below — is the single source of truth for DOM structure,
  // rather than SortableJS and Vue fighting over the same nodes.
  removeNode(evt.item);
  insertNodeAt(evt.from, evt.item, evt.oldIndex);

  const sourceColumn = props.root.links[fromIndex];
  const targetColumn = props.root.links[toIndex];
  const [moved] = sourceColumn.splice(evt.oldIndex, 1);

  if (!moved) {
    return;
  }

  // The insert into the target column is deferred to the next tick — mirroring
  // vueuse's own `moveArrayElement` exactly. Splicing both arrays synchronously in
  // the same tick let Vue's reconciliation race the manual DOM revert above: the
  // *target* NavigationColumn (a sibling component instance, so it can't reuse the
  // node the source instance is about to unmount) would sometimes mount a fresh node
  // for the moved item before the source instance's own patch got round to removing
  // the reverted one, leaving a visual duplicate behind in the source column.
  nextTick(() => {
    targetColumn.splice(evt.newIndex, 0, moved);
  });
}

// Registered at setup top level (NOT inside onMounted — vueuse's own tryOnMounted
// silently drops a nested onMounted registration, since Vue has already snapshotted
// the hook queue by the time it would run, so nothing was ever dragging before this
// fix). `watchElement: true` rebinds Sortable if the column's DOM node is ever
// replaced, so drag keeps working after any parent re-render.
columnEls.forEach((elRef, index) => {
  useSortable(elRef, props.root.links[index], {
    watchElement: true,
    group: `nav-links-${props.root.id}`,
    animation: 150,
    handle: '.nav-link-handle',
    draggable: '.nav-link-card',
    onMove: handleSortMove,
    // Handling everything through onEnd (instead of leaving vueuse's default
    // onUpdate active) keeps same-column reorders and cross-column moves on one
    // consistent code path — onEnd fires for both, with `evt.from === evt.to`
    // identifying a same-column reorder.
    onUpdate: () => {},
    onEnd: handleSortEnd,
  });
});
</script>
