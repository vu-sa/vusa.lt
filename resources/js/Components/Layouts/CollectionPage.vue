<template>
  <div ref="root" class="flex flex-col gap-4" data-slot="collection-page">
    <Head :title />

    <CollectionTitleBand :eyebrow :title :lead :entity-type>
      <template v-if="$slots.actions" #actions>
        <slot name="actions" />
      </template>
    </CollectionTitleBand>

    <CollectionQuickFilters :filters="quickFilters ?? []" @toggle="id => emit('quickFilter', id)" />

    <CollectionControlRow
      :query="source.query.value"
      :placeholder="searchPlaceholder ?? $t('Ieškoti')"
      :has-filters="source.facets.value.length > 0"
      :filters-open="isAtLeastMd ? filtersOpen : sheetOpen"
      :active-filter-count="source.activeFilterCount.value"
      :sort-by="source.sortBy.value"
      :sort-options="source.sortOptions.value"
      @search="(query, immediate) => source.search(query, immediate)"
      @toggle-filters="toggleFilters"
      @update:sort-by="source.setSortBy"
    >
      <template #view-toggle>
        <CollectionViewToggle :model-value="view" :views="availableViews" @update:model-value="setView" />
      </template>
    </CollectionControlRow>

    <CollectionFilterBar
      v-model:sheet-open="sheetOpen"
      :facets="source.facets.value"
      :open="filtersOpen"
      :is-at-least-md
      :active-count="source.activeFilterCount.value"
      @toggle="source.toggleFilter"
      @clear="source.clearFilters"
    />

    <CollectionActiveChips
      :chips="source.chips.value"
      :total="source.hasSearched.value && !source.error.value ? source.total.value : null"
      @remove="source.clearChip"
      @clear="source.clearFilters"
    />

    <CollectionResults
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
      @clear="clearAll"
      @retry="source.refresh"
      @load-more="source.loadMore"
      @select="select"
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
  </div>
</template>

<script setup lang="ts" generic="T">
import { Head, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useMediaQuery } from '@vueuse/core';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

import CollectionActiveChips from '@/Components/Collection/CollectionActiveChips.vue';
import CollectionControlRow from '@/Components/Collection/CollectionControlRow.vue';
import CollectionFilterBar from '@/Components/Collection/CollectionFilterBar.vue';
import CollectionQuickFilters from '@/Components/Collection/CollectionQuickFilters.vue';
import CollectionResults from '@/Components/Collection/CollectionResults.vue';
import CollectionTitleBand from '@/Components/Collection/CollectionTitleBand.vue';
import CollectionViewToggle from '@/Components/Collection/CollectionViewToggle.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import type { CollectionSource } from '@/Composables/useCollectionSource';
import { useCollectionView, type CollectionViewMode } from '@/Composables/useCollectionView';

const props = defineProps<{
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
}>();

const emit = defineEmits<{
  quickFilter: [id: string];
}>();

defineSlots<{
  actions: () => unknown;
  row: (props: { item: T; selected: boolean; pinned: boolean }) => unknown;
  cell: (props: { item: T; column: CollectionColumn; pinned: boolean }) => unknown;
  preview: (props: { item: T }) => unknown;
  empty: () => unknown;
}>();

const root = ref<HTMLElement | null>(null);
const isAtLeastMd = useMediaQuery('(min-width: 768px)');

const { view, availableViews, setView, filtersOpen } = useCollectionView({
  collection: props.collection,
  defaultView: props.defaultView,
});

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

function clearAll(): void {
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
