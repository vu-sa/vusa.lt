<template>
  <div class="flex min-h-0 flex-1 flex-col gap-3">
    <!-- Search input -->
    <div class="flex h-10 shrink-0 items-center gap-2 rounded-lg border bg-background px-3">
      <Search v-if="!controller.isSearching.value" class="size-4 shrink-0 text-muted-foreground/60" />
      <div v-else class="size-4 shrink-0 animate-spin rounded-full border-2 border-primary/30 border-t-primary" />
      <input
        :value="controller.query.value"
        type="text"
        :placeholder="searchPlaceholder"
        class="h-full flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground/50"
        @input="onQueryInput"
      >
    </div>

    <SearchSplitView
      v-model:selected-hit="selectedHit"
      :hits
      :is-loading="controller.isSearching.value"
      :is-loading-more="controller.isLoadingMore.value"
      :has-more="controller.hasMoreResults.value"
      :has-searched
      :error="errorMessage"
      :empty-message
      selectable
      :multiple
      :selected-ids
      :disabled-ids
      @toggle-select="$emit('toggle', $event)"
      @load-more="controller.loadMore"
    >
      <template #toolbar>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-1 flex-wrap items-center gap-2">
            <span class="text-sm text-muted-foreground">
              <template v-if="controller.totalHits.value > 0">
                {{ $t('Rasta :count rezultatų', { count: String(controller.totalHits.value) }) }}
              </template>
            </span>

            <template v-for="(values, field) in activeFilterPills" :key="field">
              <Badge variant="secondary" class="gap-1">
                {{ getFieldLabel(field) }}: {{ formatFilterValues(field, values) }}
                <button type="button" class="ml-0.5 hover:text-destructive" @click="clearFieldFilter(field)">
                  <X class="size-3" />
                </button>
              </Badge>
            </template>
            <Button
              v-if="controller.activeFilterCount.value > 0"
              variant="ghost"
              size="sm"
              class="h-6 px-2 text-xs"
              @click="controller.clearFilters"
            >
              {{ $t('Išvalyti visus') }}
            </Button>
          </div>

          <div class="flex items-center gap-2">
            <SearchFiltersPopover
              :facets="controller.facets.value"
              :filters="controller.filters.value"
              :facet-config="controller.facetConfig"
              :is-loading="controller.isLoadingFacets.value"
              :active-filter-count="controller.activeFilterCount.value"
              :total-hits="controller.totalHits.value"
              @toggle-filter="controller.toggleFilter"
              @set-filter="controller.setFilter"
              @clear-filters="controller.clearFilters"
            />

            <Select
              v-if="controller.sortOptions.value.length > 0"
              :model-value="controller.sortBy.value"
              @update:model-value="(v) => controller.setSortBy(v as string)"
            >
              <SelectTrigger class="h-8 w-[170px]">
                <SelectValue :placeholder="$t('Rikiuoti')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="option in controller.sortOptions.value" :key="option.value" :value="option.value">
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      </template>

      <template #detail="{ hit }">
        <slot name="detail" :hit>
          <SearchDetailPane :hit />
        </slot>
      </template>
    </SearchSplitView>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { X, Search } from 'lucide-vue-next';

import SearchSplitView from '../SearchSplitView.vue';
import SearchDetailPane from '../SearchDetailPane.vue';
import SearchFiltersPopover from '../SearchFiltersPopover.vue';
import { useFilterPills } from '../../Composables/useFilterPills';
import type { AdminCollectionSearchController } from '../../Composables/useAdminCollectionSearch';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

import type { SelectSearchController } from './types';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const props = defineProps<{
  controller: SelectSearchController;
  /** Maps a raw search document to a normalized hit for rendering. */
  mapHit: (doc: unknown) => NormalizedSearchHit;
  multiple: boolean;
  selectedIds: Set<string>;
  disabledIds?: Set<string>;
  emptyMessage: string;
  searchPlaceholder: string;
  /** Currently-selected hits to surface first (and keep visible) while browsing. */
  pinnedHits?: NormalizedSearchHit[];
}>();

defineEmits<{
  toggle: [hit: NormalizedSearchHit];
}>();

const selectedHit = ref<NormalizedSearchHit | null>(null);

const resultHits = computed<NormalizedSearchHit[]>(() => props.controller.results.value.map(props.mapHit));

/**
 * While browsing (no query) the currently-selected hits are pinned to the top so
 * the existing selection is visible and pre-highlighted. When a query is active,
 * results drive the order untouched. Result docs win over pinned stubs (richer detail).
 */
const hits = computed<NormalizedSearchHit[]>(() => {
  const pinned = props.pinnedHits ?? [];
  if (pinned.length === 0 || props.controller.query.value.trim() !== '') {
    return resultHits.value;
  }
  const resultIds = new Set(resultHits.value.map(h => h.id));
  const pinnedIds = new Set(pinned.map(h => h.id));
  const pinnedNotInResults = pinned.filter(h => !resultIds.has(h.id));
  const pinnedFromResults = resultHits.value.filter(h => pinnedIds.has(h.id));
  const rest = resultHits.value.filter(h => !pinnedIds.has(h.id));
  return [...pinnedNotInResults, ...pinnedFromResults, ...rest];
});

const errorMessage = computed<string | null>(() => props.controller.error.value?.userMessage ?? null);

// useFilterPills is typed against the Typesense controller, but only touches the
// shared surface both controllers expose.
const {
  hasSearched,
  activeFilterPills,
  getFieldLabel,
  formatFilterValues,
  clearFieldFilter,
  handleQueryChange,
} = useFilterPills(props.controller as unknown as AdminCollectionSearchController);

const onQueryInput = (event: Event) => {
  handleQueryChange((event.target as HTMLInputElement).value);
};
</script>
