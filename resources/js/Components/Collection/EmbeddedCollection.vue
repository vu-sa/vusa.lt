<template>
  <!-- A collection inside another page (a record's tab): the index's controls and views, without
       its title band, trash, preview pane or URL state, which all belong to the host page. -->
  <div class="flex min-w-0 max-w-full flex-col gap-3" data-slot="embedded-collection">
    <CollectionQuickFilters
      v-if="isAtLeastMd && quickFilters?.length"
      :filters="quickFilters"
      @toggle="id => emit('quickFilter', id)"
    />

    <section class="flex min-w-0 max-w-full flex-col gap-2 border-y border-border py-3" :aria-label="$t('Paieška ir filtrai')">
      <CollectionControlRow
        :query="source.query.value"
        :placeholder="searchPlaceholder ?? $t('Ieškoti')"
        :has-filters="source.facets.value.length > 0 || (!isAtLeastMd && Boolean(quickFilters?.length))"
        :filters-open="isAtLeastMd ? filtersOpen : sheetOpen"
        :active-filter-count="source.activeFilterCount.value"
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
        :quick-filters="quickFilters ?? []"
        :open="filtersOpen"
        :is-at-least-md
        :active-count="source.activeFilterCount.value"
        @toggle="source.toggleFilter"
        @quick-filter="id => emit('quickFilter', id)"
        @clear="source.clearFilters()"
      />

      <CollectionActiveChips
        v-if="source.chips.value.length > 0"
        :chips="source.chips.value"
        @remove="source.clearChip"
        @clear="source.clearFilters()"
      />
    </section>

    <CollectionResults
      :collection
      :view
      :items="source.items.value"
      :item-key
      :columns
      :row-actions
      :is-loading="source.isLoading.value"
      :is-loading-more="source.isLoadingMore.value"
      :has-searched="source.hasSearched.value"
      :has-more="source.hasMore.value"
      :error="source.error.value"
      :is-filtered
      :selected-key="null"
      :sort-by="source.sortBy.value"
      :sort-options="source.sortOptions.value"
      :total="source.total.value"
      :table-fixed
      :actions-width
      :table-min-width
      @row-action="(item, key) => emit('rowAction', item, key)"
      @row-navigate="(item, key) => emit('rowNavigate', item, key)"
      @clear="clearAll"
      @retry="source.refresh"
      @load-more="source.loadMore"
      @sort="source.setSortBy"
    >
      <template #row="slotProps">
        <slot name="row" v-bind="slotProps" />
      </template>
      <template v-if="$slots.cell" #cell="slotProps">
        <slot name="cell" v-bind="slotProps" />
      </template>
      <template v-if="$slots.empty" #empty>
        <slot name="empty" />
      </template>
    </CollectionResults>
  </div>
</template>

<script setup lang="ts" generic="T">
import { useMediaQuery } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import type { CollectionSource } from '@/Composables/useCollectionSource';
import { useCollectionControls } from '@/Composables/useCollectionControls';
import { useCollectionView, type CollectionViewMode } from '@/Composables/useCollectionView';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';

import CollectionActiveChips from './CollectionActiveChips.vue';
import CollectionControlRow from './CollectionControlRow.vue';
import CollectionFilterBar from './CollectionFilterBar.vue';
import CollectionQuickFilters from './CollectionQuickFilters.vue';
import CollectionResults from './CollectionResults.vue';
import CollectionViewToggle from './CollectionViewToggle.vue';
import type { CollectionColumn, CollectionQuickFilter } from './types';

const props = withDefaults(defineProps<{
  /** Build it with `syncToUrl: false`: the host page owns the URL. */
  source: CollectionSource<T>;
  /** Stable name; scopes the remembered view and filter-bar state apart from the index's. */
  collection: string;
  itemKey: (item: T) => string;
  defaultView?: Extract<CollectionViewMode, 'rows' | 'table'>;
  quickFilters?: CollectionQuickFilter[];
  columns?: CollectionColumn[];
  rowActions?: (item: T) => AdminRecordAction[];
  searchPlaceholder?: string;
  tableFixed?: boolean;
  actionsWidth?: string;
  tableMinWidth?: string;
}>(), {
  defaultView: 'rows',
  quickFilters: undefined,
  columns: undefined,
  rowActions: undefined,
  searchPlaceholder: undefined,
  actionsWidth: undefined,
  tableMinWidth: undefined,
});

const emit = defineEmits<{
  quickFilter: [id: string];
  rowAction: [item: T, key: string];
  rowNavigate: [item: T, key: string];
}>();

defineSlots<{
  row: (props: { item: T; index: number; actions: AdminRecordAction[]; view: CollectionViewMode; selected: boolean; pinned: boolean }) => unknown;
  cell?: (props: { item: T; actions: AdminRecordAction[]; column: CollectionColumn; pinned: boolean }) => unknown;
  empty?: () => unknown;
}>();

const isAtLeastMd = useMediaQuery('(min-width: 768px)');

const { view, availableViews, setView, filtersOpen } = useCollectionView({
  collection: props.collection,
  defaultView: props.defaultView,
  views: ['rows', 'table'],
  hasPreview: false,
  syncToUrl: false,
});

const { sheetOpen, toggleFilters } = useCollectionControls(isAtLeastMd, filtersOpen, () => { props.source.loadFacets?.(); });

const isFiltered = computed(() => props.source.query.value.trim() !== '' || props.source.activeFilterCount.value > 0);

function clearAll(): void {
  props.source.clearFilters();
  props.source.search('', true);
}

</script>
