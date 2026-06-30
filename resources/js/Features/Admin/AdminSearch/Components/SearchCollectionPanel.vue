<template>
  <SearchSplitView
    v-model:selected-hit="selectedHit"
    :hits="hits"
    :is-loading="controller.isSearching.value"
    :is-loading-more="controller.isLoadingMore.value"
    :has-more="controller.hasMoreResults.value"
    :has-searched="hasSearched"
    :error="controller.error.value"
    :empty-message="emptyMessage"
    @load-more="controller.loadMore"
  >
    <template #toolbar>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <!-- Count + active filter pills -->
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

        <!-- Filters + sort -->
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
  </SearchSplitView>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { X } from 'lucide-vue-next';

import SearchSplitView from './SearchSplitView.vue';
import SearchFiltersPopover from './SearchFiltersPopover.vue';
import { useAdminCollectionSearch } from '../Composables/useAdminCollectionSearch';
import { useFilterPills } from '../Composables/useFilterPills';
import { adminCollectionToKey, normalizeHit, type MapperContext, type NormalizedSearchHit } from '../Utils/searchHitMappers';
import type { AdminCollection } from '../Types/AdminSearchTypes';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

const props = defineProps<{
  collection: AdminCollection;
  /** Page-owned query (hero input); changes trigger a debounced search. */
  query: string;
  emptyMessage: string;
}>();

const controller = useAdminCollectionSearch({
  collection: props.collection,
  loadFacetsOnMount: true,
  searchOnMount: true,
  syncToUrl: true,
  preserveUrlKeys: ['tab'],
  perPage: 30,
});

const {
  hasSearched,
  activeFilterPills,
  getFieldLabel,
  formatFilterValues,
  clearFieldFilter,
  handleQueryChange,
} = useFilterPills(controller);

const selectedHit = ref<NormalizedSearchHit | null>(null);

const collectionKey = adminCollectionToKey(props.collection);

// Duties flag cross-tenant rows; give the mapper the user's accessible tenants.
const mapperCtx = computed<MapperContext | undefined>(() =>
  props.collection === 'duties'
    ? {
        ownTenantIds: controller.adminSearch.getCollectionTenantIds('duties'),
        isSuperAdmin: controller.adminSearch.isSuperAdmin.value,
      }
    : undefined,
);

const hits = computed<NormalizedSearchHit[]>(() =>
  controller.results.value.map(doc => normalizeHit(collectionKey, doc, mapperCtx.value)),
);

watch(
  () => props.query,
  (newQuery) => {
    if (newQuery !== controller.query.value) {
      handleQueryChange(newQuery);
    }
  },
);
</script>
