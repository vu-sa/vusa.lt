<template>
  <div class="relative" data-slot="collection-results">
    <!-- Overlaid, not in flow: inserting it on every sort or search pushed the table down and back. -->
    <TopProgressBar v-if="refreshing" class="absolute inset-x-0 top-0" :label="$t('Kraunami duomenys')" />

    <div class="mb-3 flex flex-wrap items-center justify-between gap-3" data-slot="collection-results-toolbar">
      <div class="flex min-h-9 items-center gap-4">
        <p v-if="hasSearched && !error" class="text-xs text-muted-foreground" aria-live="polite">
          {{ $t('Rasta') }} <span class="font-bold tabular-nums text-foreground">{{ total }}</span>
        </p>
        <label v-if="selectable && view !== 'table' && allItems.length > 0" class="inline-flex items-center gap-3 text-xs font-bold uppercase tracking-wide text-muted-foreground">
          <Checkbox
            :model-value="allSelectedState"
            :aria-label="$t('Pažymėti visus rodomus')"
            @update:model-value="value => table.toggleAllRowsSelected(value === true)"
          />
          {{ $t('Pažymėti visus rodomus') }}
        </label>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <label
          v-if="sortOptions.length > 1"
          class="relative flex h-9 min-w-0 items-center gap-2 border border-border bg-background pr-9 pl-3 focus-within:border-brand pointer-coarse:min-h-11"
        >
          <span class="text-[11px] font-bold uppercase tracking-wide text-muted-foreground">{{ $t('Rikiuoti') }}</span>
          <span class="max-w-44 truncate text-sm font-bold text-foreground">{{ sortOptions.find(option => option.value === sortBy)?.label ?? sortBy }}</span>
          <ChevronDown class="pointer-events-none absolute right-3 size-4 text-muted-foreground" aria-hidden="true" />
          <select
            :value="sortBy"
            :aria-label="$t('Rikiuoti')"
            class="absolute inset-0 z-10 size-full cursor-pointer opacity-0"
            @change="emit('sort', ($event.target as HTMLSelectElement).value)"
          >
            <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </label>

        <DropdownMenu v-if="view === 'table' && hideableColumns.length > 0">
          <DropdownMenuTrigger as-child>
            <button type="button" :class="controlVariants({ size: 'sm' })">
              <Columns3 class="size-4" aria-hidden="true" />
              {{ $t('Stulpeliai') }}
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuCheckboxItem
              v-for="column in hideableColumns"
              :key="column.key"
              :model-value="table.getColumn(column.key)?.getIsVisible() ?? true"
              @update:model-value="value => table.getColumn(column.key)?.toggleVisibility(value === true)"
              @select.prevent
            >
              {{ column.label }}
            </DropdownMenuCheckboxItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <div
      v-if="error"
      role="alert"
      class="flex flex-wrap items-center justify-between gap-3 border border-status-danger-border bg-status-danger-surface px-4 py-3 text-sm text-status-danger"
    >
      <span>{{ error }}</span>
      <Button variant="outline" size="sm" @click="emit('retry')">
        {{ $t('Bandyti dar kartą') }}
      </Button>
    </div>

    <CollectionSkeleton v-else-if="isInitialLoad" :view-mode="view === 'table' ? 'table' : 'rows'" :rows="8" />

    <template v-else-if="items.length === 0 && pinnedList.length === 0">
      <EmptyState v-if="isFiltered" mode="no-results" @clear="emit('clear')" />
      <slot v-else name="empty">
        <EmptyState mode="empty" :title="$t('Nieko dar nėra')" />
      </slot>
    </template>

    <div
      v-else
      :class="view === 'preview' ? 'grid gap-6 xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]' : ''"
    >
      <div class="min-w-0">
        <div v-if="view === 'table'" class="overflow-x-auto border border-border" data-slot="collection-table">
          <Table :class="tableFixed ? 'table-fixed min-w-[52rem]' : undefined">
            <TableHeader>
              <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <TableHead
                  v-for="header in headerGroup.headers"
                  :key="header.id"
                  :class="[
                    header.id === SELECT_COLUMN_ID ? 'w-12' : columnOf(header.id)?.class,
                    columnOf(header.id)?.pinned ? 'sticky right-0 z-10 bg-secondary/50' : '',
                  ]"
                  :aria-sort="ariaSort(header.column.getIsSorted())"
                >
                  <template v-if="header.id === SELECT_COLUMN_ID">
                    <Checkbox
                      :model-value="allSelectedState"
                      :aria-label="$t('Pažymėti visus rodomus')"
                      @update:model-value="value => table.toggleAllRowsSelected(value === true)"
                    />
                  </template>
                  <button
                    v-else-if="header.column.getCanSort()"
                    type="button"
                    class="-mx-1 inline-flex items-center gap-1.5 px-1 uppercase tracking-[inherit] hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                    @click="header.column.toggleSorting(header.column.getIsSorted() === 'asc')"
                  >
                    {{ header.column.columnDef.header }}
                    <ArrowUp v-if="header.column.getIsSorted() === 'asc'" class="size-3.5 text-brand" aria-hidden="true" />
                    <ArrowDown v-else-if="header.column.getIsSorted() === 'desc'" class="size-3.5 text-brand" aria-hidden="true" />
                    <ArrowUpDown v-else class="size-3.5 opacity-50" aria-hidden="true" />
                  </button>
                  <template v-else>
                    {{ header.column.columnDef.header }}
                  </template>
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow
                v-for="row in table.getRowModel().rows"
                :key="row.id"
                :data-state="row.getIsSelected() ? 'selected' : undefined"
                class="group"
              >
                <TableCell
                  v-for="cell in row.getVisibleCells()"
                  :key="cell.id"
                  :class="[
                    cell.column.id === SELECT_COLUMN_ID ? 'w-12' : columnOf(cell.column.id)?.class,
                    columnOf(cell.column.id)?.pinned ? 'sticky right-0 z-10 bg-background group-hover:bg-secondary/40 group-data-[state=selected]:bg-secondary' : '',
                  ]"
                >
                  <Checkbox
                    v-if="cell.column.id === SELECT_COLUMN_ID && row.getCanSelect()"
                    :model-value="row.getIsSelected()"
                    :aria-label="$t('Pažymėti')"
                    @click.capture="rememberShift"
                    @update:model-value="value => toggleRow(row.id, value === true, { range: shiftHeld })"
                  />
                  <slot
                    v-else-if="cell.column.id !== SELECT_COLUMN_ID"
                    name="cell"
                    :item="row.original"
                    :column="columnOf(cell.column.id)!"
                    :pinned="isPinned(row.original)"
                  />
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <ul v-else ref="rowsEl" class="divide-y divide-border/60 border-y border-border" data-slot="collection-rows">
          <li
            v-for="row in table.getRowModel().rows"
            :key="row.id"
            :data-collection-key="row.id"
            :class="[
              'flex items-stretch transition-colors hover:bg-secondary/40',
              view === 'preview' && 'cursor-pointer',
              (view === 'preview' && selectedKey === row.id) || row.getIsSelected() ? 'bg-secondary' : '',
            ]"
          >
            <div v-if="selectable" class="flex w-10 shrink-0 items-center pl-3 sm:w-12 sm:pl-4">
              <Checkbox
                v-if="row.getCanSelect()"
                :model-value="row.getIsSelected()"
                :aria-label="$t('Pažymėti')"
                @click.capture="rememberShift"
                @update:model-value="value => toggleRow(row.id, value === true, { range: shiftHeld })"
              />
            </div>
            <div class="min-w-0 flex-1">
              <slot name="row" :item="row.original" :view :selected="selectedKey === row.id" :pinned="isPinned(row.original)" />
            </div>
          </li>
        </ul>

        <CollectionLoadMore :has-more :loading="isLoadingMore" @load="emit('loadMore')" />
      </div>

      <aside
        v-if="view === 'preview'"
        :class="[
          'hidden min-w-0 xl:sticky xl:block xl:self-start xl:overflow-y-auto xl:border xl:border-border',
          'xl:top-[calc(var(--shell-chrome-height,0px)+1rem)]',
          'xl:max-h-[calc(var(--shell-scroll-height,100svh)-var(--shell-chrome-height,0px)-2rem)]',
        ]"
        data-slot="collection-preview"
      >
        <slot v-if="selectedItem" name="preview" :item="selectedItem" />
        <p v-else class="p-6 text-sm text-muted-foreground">
          {{ $t('Pasirink įrašą, kad matytum jo peržiūrą.') }}
        </p>
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts" generic="T">
import { trans as $t } from 'laravel-vue-i18n';
import { useEventListener } from '@vueuse/core';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronDown, Columns3 } from 'lucide-vue-next';
import { computed, ref, toRef } from 'vue';

import CollectionLoadMore from './CollectionLoadMore.vue';
import type { CollectionColumn } from './types';

import { CollectionSkeleton, EmptyState, TopProgressBar } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { controlVariants } from '@/Components/ui/control';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import type { CollectionSortOption } from '@/Composables/useCollectionSource';
import { SELECT_COLUMN_ID, useCollectionTable } from '@/Composables/useCollectionTable';
import type { CollectionViewMode } from '@/Composables/useCollectionView';

const props = withDefaults(defineProps<{
  collection: string;
  view: CollectionViewMode;
  items: readonly T[];
  /** Rows shown above the results while the index catches up with a change the user just made. */
  pinned?: readonly T[];
  itemKey: (item: T) => string;
  columns?: CollectionColumn[];
  isLoading: boolean;
  isLoadingMore: boolean;
  hasSearched: boolean;
  hasMore: boolean;
  error: string | null;
  /** A query or filter is narrowing the list: an empty result is "no results", not "nothing yet". */
  isFiltered: boolean;
  selectedKey: string | null;
  selectable?: boolean;
  canSelect?: (item: T) => boolean;
  sortBy?: string;
  sortOptions?: CollectionSortOption[];
  total: number;
  tableFixed?: boolean;
}>(), {
  pinned: () => [],
  columns: () => [],
  canSelect: undefined,
  sortBy: '',
  sortOptions: () => [],
});

const selection = defineModel<string[]>('selection', { default: () => [] });

const emit = defineEmits<{
  clear: [];
  retry: [];
  loadMore: [];
  select: [key: string];
  sort: [value: string];
}>();

defineSlots<{
  row: (props: { item: T; view: CollectionViewMode; selected: boolean; pinned: boolean }) => unknown;
  cell: (props: { item: T; column: CollectionColumn; pinned: boolean }) => unknown;
  preview: (props: { item: T }) => unknown;
  empty: () => unknown;
}>();

const pinnedList = computed(() => {
  const present = new Set(props.items.map(props.itemKey));

  return props.pinned.filter(item => !present.has(props.itemKey(item)));
});

const allItems = computed<readonly T[]>(() => [...pinnedList.value, ...props.items]);

const { table, hideableColumns, toggleRow } = useCollectionTable<T>({
  collection: props.collection,
  items: allItems,
  itemKey: props.itemKey,
  columns: toRef(props, 'columns'),
  selectable: toRef(props, 'selectable'),
  canSelect: item => props.canSelect?.(item) ?? true,
  selection,
  sortBy: toRef(props, 'sortBy'),
  sortOptions: toRef(props, 'sortOptions'),
  setSortBy: value => emit('sort', value),
});

// The checkbox's update event carries no modifiers, so the click that precedes it records Shift.
let shiftHeld = false;
const rememberShift = (event: MouseEvent) => {
  shiftHeld = event.shiftKey;
};

const columnOf = (id: string) => props.columns.find(column => column.key === id);

const allSelectedState = computed<boolean | 'indeterminate'>(() => {
  if (table.getIsAllRowsSelected()) {
    return true;
  }

  return table.getIsSomeRowsSelected() ? 'indeterminate' : false;
});

function ariaSort(sorted: false | 'asc' | 'desc'): 'ascending' | 'descending' | undefined {
  if (sorted === 'asc') {
    return 'ascending';
  }

  return sorted === 'desc' ? 'descending' : undefined;
}

const isInitialLoad = computed(() => !props.hasSearched && props.items.length === 0);
const refreshing = computed(() => props.isLoading && props.items.length > 0);
const selectedItem = computed(() => allItems.value.find(item => props.itemKey(item) === props.selectedKey) ?? null);
const isPinned = (item: T) => pinnedList.value.some(candidate => props.itemKey(candidate) === props.itemKey(item));

const rowsEl = ref<HTMLElement | null>(null);

/**
 * In the preview view a row selects on click. Modified title-link clicks keep their browser
 * meaning, and secondary controls inside the row are left alone.
 * Delegated on the list so rows stay plain markup.
 */
useEventListener(rowsEl, 'click', (event: MouseEvent) => {
  if (props.view !== 'preview' || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) {
    return;
  }

  const target = event.target as Element | null;
  const row = target?.closest('[data-collection-key]');
  if (!row) {
    return;
  }

  const opener = target?.closest('[data-collection-open]');
  if (!opener && target?.closest('a, button, input, select, textarea, [role="button"], [role="checkbox"]')) {
    return;
  }

  const key = row.getAttribute('data-collection-key');
  if (!key) {
    return;
  }

  if (opener) {
    event.preventDefault();
    event.stopPropagation();
  }
  emit('select', key);
}, { capture: true });
</script>
