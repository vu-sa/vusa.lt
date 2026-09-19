<template>
  <div data-slot="collection-results">
    <TopProgressBar v-if="refreshing" class="mb-2" :label="$t('Kraunami duomenys')" />

    <div
      v-if="error"
      role="alert"
      class="flex flex-wrap items-center justify-between gap-3 border border-status-danger-border bg-status-danger-surface px-4 py-3 text-sm text-status-danger"
    >
      <span>{{ error }}</span>
      <Button variant="brand-outline" size="sm" @click="emit('retry')">
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
        <div v-if="view === 'table'" class="overflow-x-auto border-y border-border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead v-for="column in columns" :key="column.key" :class="column.class">
                  {{ column.label }}
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in allItems" :key="itemKey(item)">
                <TableCell v-for="column in columns" :key="column.key" :class="column.class">
                  <slot name="cell" :item :column :pinned="isPinned(item)" />
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <ul v-else ref="rowsEl" class="divide-y divide-border border-y border-border" data-slot="collection-rows">
          <li
            v-for="item in allItems"
            :key="itemKey(item)"
            :data-collection-key="itemKey(item)"
            :class="view === 'preview' && selectedKey === itemKey(item) ? 'bg-secondary' : ''"
          >
            <slot name="row" :item :selected="selectedKey === itemKey(item)" :pinned="isPinned(item)" />
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
import { computed, ref } from 'vue';

import CollectionLoadMore from './CollectionLoadMore.vue';
import type { CollectionColumn } from './types';

import { CollectionSkeleton, EmptyState, TopProgressBar } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import type { CollectionViewMode } from '@/Composables/useCollectionView';

const props = defineProps<{
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
}>();

const emit = defineEmits<{
  clear: [];
  retry: [];
  loadMore: [];
  select: [key: string];
}>();

defineSlots<{
  row: (props: { item: T; selected: boolean; pinned: boolean }) => unknown;
  cell: (props: { item: T; column: CollectionColumn; pinned: boolean }) => unknown;
  preview: (props: { item: T }) => unknown;
  empty: () => unknown;
}>();

const pinnedList = computed(() => {
  const present = new Set(props.items.map(props.itemKey));

  return (props.pinned ?? []).filter(item => !present.has(props.itemKey(item)));
});

const allItems = computed(() => [...pinnedList.value, ...props.items]);

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
