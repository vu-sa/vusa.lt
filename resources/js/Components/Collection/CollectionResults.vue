<template>
  <div data-slot="collection-results">
    <TopProgressBar v-if="refreshing" class="mb-2" :label="$t('Kraunami duomenys')" />

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
        <div v-if="(selectable && view !== 'table') || (view === 'table' && hideableColumns.length > 0)" class="mb-3 flex items-center justify-between gap-3">
          <label v-if="selectable && view !== 'table'" class="inline-flex items-center gap-3 text-xs font-bold uppercase tracking-wide text-muted-foreground">
            <Checkbox
              :model-value="allSelectedState"
              :aria-label="$t('Pažymėti visus rodomus')"
              @update:model-value="value => table.toggleAllRowsSelected(value === true)"
            />
            {{ $t('Pažymėti visus rodomus') }}
          </label>
          <span v-else />

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

        <div v-if="view === 'table'" class="overflow-x-auto border border-border" data-slot="collection-table">
          <Table>
            <TableHeader>
              <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <TableHead
                  v-for="header in headerGroup.headers"
                  :key="header.id"
                  :class="header.id === SELECT_COLUMN_ID ? 'w-12' : columnOf(header.id)?.class"
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
                  :class="cell.column.id === SELECT_COLUMN_ID ? 'w-12' : columnOf(cell.column.id)?.class"
                >
                  <Checkbox
                    v-if="cell.column.id === SELECT_COLUMN_ID && row.getCanSelect()"
                    :model-value="row.getIsSelected()"
                    :aria-label="$t('Pažymėti')"
                    @update:model-value="value => row.toggleSelected(value === true)"
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
              (view === 'preview' && selectedKey === row.id) || row.getIsSelected() ? 'bg-secondary' : '',
            ]"
          >
            <div v-if="selectable" class="flex w-10 shrink-0 items-center pl-3 sm:w-12 sm:pl-4">
              <Checkbox
                v-if="row.getCanSelect()"
                :model-value="row.getIsSelected()"
                :aria-label="$t('Pažymėti')"
                @update:model-value="value => row.toggleSelected(value === true)"
              />
            </div>
            <div class="min-w-0 flex-1">
              <slot name="row" :item="row.original" :selected="selectedKey === row.id" :pinned="isPinned(row.original)" />
            </div>
          </li>
        </ul>

        <CollectionLoadMore :has-more :loading="isLoadingMore" @load="emit('loadMore')" />
      </div>

      <aside
        v-if="view === 'preview'"
        class="hidden min-w-0 xl:sticky xl:top-4 xl:block xl:max-h-[calc(100dvh-8rem)] xl:self-start xl:overflow-y-auto xl:border xl:border-border"
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
import { ArrowDown, ArrowUp, ArrowUpDown, Columns3 } from 'lucide-vue-next';
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
  row: (props: { item: T; selected: boolean; pinned: boolean }) => unknown;
  cell: (props: { item: T; column: CollectionColumn; pinned: boolean }) => unknown;
  preview: (props: { item: T }) => unknown;
  empty: () => unknown;
}>();

const pinnedList = computed(() => {
  const present = new Set(props.items.map(props.itemKey));

  return props.pinned.filter(item => !present.has(props.itemKey(item)));
});

const allItems = computed<readonly T[]>(() => [...pinnedList.value, ...props.items]);

const { table, hideableColumns } = useCollectionTable<T>({
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
 * In the preview view a row's main link selects instead of navigating. Modified clicks keep
 * their browser meaning (new tab), and secondary actions inside the row are left alone.
 * Delegated on the list so rows stay plain markup.
 */
useEventListener(rowsEl, 'click', (event: MouseEvent) => {
  if (props.view !== 'preview' || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) {
    return;
  }

  const target = event.target as Element | null;
  if (!target?.closest('a[data-collection-open]')) {
    return;
  }

  const key = target.closest('[data-collection-key]')?.getAttribute('data-collection-key');
  if (!key) {
    return;
  }

  event.preventDefault();
  event.stopPropagation();
  emit('select', key);
}, { capture: true });
</script>
