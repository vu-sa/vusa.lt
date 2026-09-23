<template>
  <div ref="root" class="flex flex-col gap-4" data-slot="collection-page">
    <Head :title />

    <CollectionTitleBand :eyebrow :title :lead :entity-type>
      <template v-if="$slots.actions" #actions>
        <slot name="actions" />
      </template>
    </CollectionTitleBand>

    <CollectionQuickFilters :filters="quickFilters ?? []" @toggle="id => emit('quickFilter', id)" />

    <section class="flex flex-col gap-3 border-y border-border py-4" :aria-label="$t('Paieška ir filtrai')">
      <CollectionControlRow
        :query="source.query.value"
        :placeholder="searchPlaceholder ?? $t('Ieškoti')"
        :has-filters="source.facets.value.length > 0 || Boolean(trash && (trash.active || trash.count > 0))"
        :filters-open="isAtLeastMd ? filtersOpen : sheetOpen"
        :active-filter-count="source.activeFilterCount.value + (trash?.active ? 1 : 0)"
        @search="(query, immediate) => source.search(query, immediate)"
        @toggle-filters="toggleFilters"
      >
        <template #view-toggle>
          <CollectionViewToggle :model-value="view" :views="availableViews" @update:model-value="setView" />
        </template>
      </CollectionControlRow>

      <CollectionFilterBar
        v-model:sheet-open="sheetOpen"
        :facets="source.facets.value"
        :trash="trash && (trash.active || trash.count > 0) ? trash : undefined"
        :open="filtersOpen"
        :is-at-least-md
        :active-count="source.activeFilterCount.value + (trash?.active ? 1 : 0)"
        @toggle="source.toggleFilter"
        @toggle-trash="toggleTrash"
        @clear="clearFilters"
      />

      <CollectionActiveChips
        v-if="activeChips.length > 0"
        :chips="activeChips"
        @remove="removeChip"
        @clear="clearFilters"
      />
    </section>

    <p
      v-if="trash?.active"
      class="flex items-center gap-2 border-l-2 border-status-attention bg-status-attention-surface px-4 py-3 text-sm text-status-attention"
      role="status"
    >
      <Trash2 class="size-4 shrink-0" aria-hidden="true" />
      {{ $t('Rodomi ištrinti įrašai. Juos gali atkurti arba ištrinti visam laikui.') }}
    </p>

    <CollectionResults
      v-model:selection="selection"
      :collection
      :view
      :items="source.items.value"
      :pinned="pinnedItems"
      :item-key
      :columns
      :is-loading="source.isLoading.value"
      :is-loading-more="source.isLoadingMore.value"
      :has-searched="source.hasSearched.value"
      :has-more="source.hasMore.value"
      :error="source.error.value"
      :is-filtered
      :selected-key
      :selectable
      :can-select
      :sort-by="source.sortBy.value"
      :sort-options="source.sortOptions.value"
      :total="source.total.value"
      :table-fixed
      @clear="clearAll"
      @retry="source.refresh"
      @load-more="source.loadMore"
      @select="select"
      @sort="source.setSortBy"
    >
      <template #row="slotProps">
        <slot name="row" v-bind="slotProps" />
      </template>
      <template v-if="$slots.cell" #cell="slotProps">
        <slot name="cell" v-bind="slotProps" />
      </template>
      <template v-if="$slots.preview" #preview="slotProps">
        <slot name="preview" v-bind="slotProps" />
      </template>
      <template v-if="$slots.empty" #empty>
        <slot name="empty" />
      </template>
    </CollectionResults>

    <CollectionSelectionBar
      v-if="selectable && selection.length > 0"
      :count="selection.length"
      :count-label="$t('Pažymėta')"
      @clear="selection = []"
    >
      <slot name="bulk-actions" :selected="selectedItems" :clear="() => { selection = []; }" />
    </CollectionSelectionBar>
  </div>
</template>

<script setup lang="ts" generic="T">
import { Head, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useMediaQuery } from '@vueuse/core';
import { Trash2 } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

import CollectionActiveChips from '@/Components/Collection/CollectionActiveChips.vue';
import CollectionControlRow from '@/Components/Collection/CollectionControlRow.vue';
import CollectionFilterBar from '@/Components/Collection/CollectionFilterBar.vue';
import CollectionQuickFilters from '@/Components/Collection/CollectionQuickFilters.vue';
import CollectionResults from '@/Components/Collection/CollectionResults.vue';
import CollectionSelectionBar from '@/Components/Collection/CollectionSelectionBar.vue';
import CollectionTitleBand from '@/Components/Collection/CollectionTitleBand.vue';
import CollectionViewToggle from '@/Components/Collection/CollectionViewToggle.vue';
import type { CollectionColumn, CollectionQuickFilter, CollectionTrash } from '@/Components/Collection/types';
import type { CollectionChip, CollectionSource } from '@/Composables/useCollectionSource';
import { useCollectionView, type CollectionViewMode } from '@/Composables/useCollectionView';

const props = withDefaults(defineProps<{
  source: CollectionSource<T>;
  /** Stable name; scopes the remembered view and filter-bar state. */
  collection: string;
  entityType: string;
  eyebrow: string;
  title: string;
  lead?: string;
  defaultView: CollectionViewMode;
  itemKey: (item: T) => string;
  quickFilters?: CollectionQuickFilter[];
  columns?: CollectionColumn[];
  /** Records the user just changed, shown until the search index reflects them (O1's Typesense trap). */
  pinnedItems?: readonly T[];
  searchPlaceholder?: string;
  /** Views this collection offers; the viewport narrows them further. */
  availableViews?: CollectionViewMode[];
  /** Shows checkboxes; selected rows reach the `bulk-actions` slot in a bottom-pinned bar. */
  selectable?: boolean;
  /** Rows that may not be selected get no checkbox. */
  canSelect?: (item: T) => boolean;
  /** Soft-deleted records as a filter over this same page (`?showDeleted=true`). */
  trash?: CollectionTrash;
  /** URL params that scope the server response (e.g. `tenant`, `lang`) and so survive the trash toggle. */
  keepParams?: string[];
  /** Keep table columns within assigned widths when cells contain long text. */
  tableFixed?: boolean;
}>(), {
  lead: undefined,
  quickFilters: undefined,
  columns: undefined,
  pinnedItems: undefined,
  searchPlaceholder: undefined,
  availableViews: undefined,
  canSelect: undefined,
  trash: undefined,
  keepParams: undefined,
});

const emit = defineEmits<{
  quickFilter: [id: string];
}>();

defineSlots<{
  'actions': () => unknown;
  'row': (props: { item: T; view: CollectionViewMode; selected: boolean; pinned: boolean }) => unknown;
  'cell': (props: { item: T; column: CollectionColumn; pinned: boolean }) => unknown;
  'preview': (props: { item: T }) => unknown;
  'empty': () => unknown;
  'bulk-actions': (props: { selected: T[]; clear: () => void }) => unknown;
}>();

const root = ref<HTMLElement | null>(null);
const isAtLeastMd = useMediaQuery('(min-width: 768px)');

const { view, availableViews, setView, filtersOpen } = useCollectionView({
  collection: props.collection,
  defaultView: props.defaultView,
  views: props.availableViews,
});

// --- Selection (TanStack row selection, keyed by itemKey) ------------------------------------

/** Selected item keys; bind `v-model:selection` when the page must clear it itself (after a bulk decision). */
const selection = defineModel<string[]>('selection', { default: () => [] });

const selectedItems = computed<T[]>(() => {
  const keys = new Set(selection.value);

  return [...(props.pinnedItems ?? []), ...props.source.items.value].filter(item => keys.has(props.itemKey(item)));
});

// A selection made against one result set must not silently act on a different one.
watch(
  () => [props.source.query.value, props.source.filters.value, props.selectable] as const,
  () => {
    selection.value = [];
  },
);

function toggleTrash(): void {
  const url = new URL(window.location.href);
  const next = new URL(url.pathname, url.origin);

  for (const param of props.keepParams ?? []) {
    const value = url.searchParams.get(param);
    if (value !== null) {
      next.searchParams.set(param, value);
    }
  }

  if (!props.trash?.active) {
    next.searchParams.set('showDeleted', 'true');
  }

  router.visit(next.toString());
}

/** Below md the Filtrai button opens the sheet instead of the inline row. */
const sheetOpen = ref(false);

function toggleFilters(): void {
  if (isAtLeastMd.value) {
    filtersOpen.value = !filtersOpen.value;
  }
  else {
    sheetOpen.value = true;
  }
}

const isFiltered = computed(() => props.source.query.value.trim() !== '' || props.source.activeFilterCount.value > 0);

const activeChips = computed<CollectionChip[]>(() => [
  ...(props.trash?.active ? [{ id: '__trash', label: $t('Ištrinti') }] : []),
  ...props.source.chips.value,
]);

function removeChip(id: string): void {
  if (id === '__trash') {
    toggleTrash();
  }
  else {
    props.source.clearChip(id);
  }
}

function clearFilters(): void {
  if (props.trash?.active) {
    toggleTrash();
  }
  else {
    props.source.clearFilters();
  }
}

function clearAll(): void {
  if (props.trash?.active) {
    toggleTrash();
    return;
  }

  props.source.clearFilters();
  props.source.search('', true);
}

// --- The open preview lives in the URL (`?item=`, U1) ---------------------------------------

const selectedKey = ref<string | null>(new URLSearchParams(window.location.search).get('item'));

function select(key: string): void {
  selectedKey.value = key;

  const url = new URL(window.location.href);
  url.searchParams.set('item', key);
  window.history.replaceState(window.history.state, '', url.toString());
}

// The preview pane is never empty while there is something to show.
watch(
  () => [view.value, props.source.items.value] as const,
  ([currentView, items]) => {
    if (currentView !== 'preview' || items.length === 0) {
      return;
    }
    if (!items.some(item => props.itemKey(item) === selectedKey.value)) {
      select(props.itemKey(items[0]));
    }
  },
  { immediate: true },
);

// --- Back navigation lands where the user left (O3) -----------------------------------------

const scrollKey = () => `admin-collection-scroll:${window.location.pathname}${window.location.search}`;
const scroller = () => root.value?.closest('main') ?? null;

function rememberScroll(): void {
  const element = scroller();
  if (element) {
    sessionStorage.setItem(scrollKey(), String(element.scrollTop));
  }
}

const stopBefore = router.on('before', rememberScroll);
onBeforeUnmount(() => {
  rememberScroll();
  stopBefore();
});

// Rows arrive after mount, so the saved position can only be applied once they are there.
let restored = false;
watch(
  () => props.source.hasSearched.value,
  async (searched) => {
    if (!searched || restored) {
      return;
    }
    restored = true;

    const saved = Number(sessionStorage.getItem(scrollKey()));
    if (saved > 0) {
      await nextTick();
      scroller()?.scrollTo({ top: saved });
    }
  },
  { immediate: true },
);
</script>
