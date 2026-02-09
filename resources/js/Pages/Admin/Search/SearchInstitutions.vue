<template>
  <AdminContentPage :breadcrumb-options>
    <AdminSearchLayout
      :title="$t('Institucijų paieška')"
      :description="$t('Ieškokite institucijų pagal pavadinimą, el. paštą ar padalinį')"
      :query="searchController.query.value"
      :search-placeholder="$t('Ieškoti institucijų...')"
      :total-hits="searchController.totalHits.value"
      :sort-by="searchController.sortBy.value"
      :sort-options="searchController.sortOptions.value"
      :active-filter-count="searchController.activeFilterCount.value"
      :has-more-results="searchController.hasMoreResults.value"
      :is-loading-more="searchController.isLoadingMore.value"
      @update:query="handleQueryChange"
      @update:sort-by="searchController.setSortBy"
      @search="handleSearch"
      @clear-filters="searchController.clearFilters"
      @load-more="searchController.loadMore"
    >
      <!-- Header Actions (Create Button) -->
      <template v-if="can?.create" #header-actions>
        <Link :href="route('institutions.create')">
          <Button>
            <Plus class="size-4 mr-2" />
            {{ $t('Sukurti instituciją') }}
          </Button>
        </Link>
      </template>

      <!-- Facet Sidebar -->
      <template #sidebar>
        <AdminFacetSidebar
          :facets="searchController.facets.value"
          :filters="searchController.filters.value"
          :facet-config="searchController.facetConfig"
          :is-loading="searchController.isLoadingFacets.value"
          :active-filter-count="searchController.activeFilterCount.value"
          @toggle-filter="searchController.toggleFilter"
          @set-filter="searchController.setFilter"
          @clear-filters="searchController.clearFilters"
        />
      </template>

      <!-- Active Filters Pills -->
      <template #active-filters>
        <Badge
          v-for="(values, field) in activeFilterPills"
          :key="field"
          variant="secondary"
          class="gap-1"
        >
          {{ getFieldLabel(field) }}: {{ formatFilterValues(field, values) }}
          <button
            type="button"
            class="ml-1 hover:text-destructive"
            @click="clearFieldFilter(field)"
          >
            <X class="size-3" />
          </button>
        </Badge>
      </template>

      <!-- Results -->
      <template #results>
        <AdminSearchResults
          :is-searching="searchController.isSearching.value"
          :has-results="searchController.hasResults.value"
          :has-searched
          :has-active-filters="searchController.hasActiveFilters.value"
          :error="searchController.error.value"
          :empty-message="$t('Nerasta institucijų pagal jūsų paiešką')"
          layout="grid"
          :grid-cols="2"
          @retry="searchController.refresh"
          @clear-filters="searchController.clearFilters"
        >
          <InstitutionSearchCard
            v-for="institution in typedResults"
            :key="institution.id"
            :institution
          />
        </AdminSearchResults>
      </template>
    </AdminSearchLayout>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link } from '@inertiajs/vue3';
import { X, Plus } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import { getFacetValueLabel } from '@/Features/Admin/AdminSearch/Config/collectionFacetConfig';
import AdminSearchLayout from '@/Features/Admin/AdminSearch/Components/AdminSearchLayout.vue';
import AdminFacetSidebar from '@/Features/Admin/AdminSearch/Components/AdminFacetSidebar.vue';
import AdminSearchResults from '@/Features/Admin/AdminSearch/Components/AdminSearchResults.vue';
import InstitutionSearchCard from '@/Features/Admin/AdminSearch/Components/Cards/InstitutionSearchCard.vue';
import type { InstitutionSearchResult } from '@/Composables/useAdminSearch';
import { InstitutionIcon } from '@/Components/icons';

// Props from controller
interface Props {
  can?: {
    create?: boolean;
  };
}

const { can } = defineProps<Props>();

// Breadcrumbs
const breadcrumbOptions = [
  { label: $t('Paieška'), icon: InstitutionIcon },
  { label: $t('Institucijos') },
];

// Track if user has performed a search
const hasSearched = ref(false);

// Initialize search controller for institutions collection
const searchController = useAdminCollectionSearch({
  collection: 'institutions',
  loadFacetsOnMount: true,
  searchOnMount: true,
  syncToUrl: true,
  perPage: 24,
});

// Type the results as InstitutionSearchResult[]
const typedResults = computed(() => {
  return searchController.results.value as InstitutionSearchResult[];
});

// Compute active filter pills (excluding empty arrays and query)
// Iterates over known facetConfig fields (whitelist) to avoid prototype pollution
const activeFilterPills = computed(() => {
  const pills: Record<string, (string | number)[]> = {};
  const filters = searchController.filters.value;

  for (const fieldConfig of searchController.facetConfig.fields) {
    const value = filters[fieldConfig.field];
    if (Array.isArray(value) && value.length > 0) {
      pills[fieldConfig.field] = value;
    }
    else if (typeof value === 'string' && value.length > 0) {
      pills[fieldConfig.field] = [value];
    }
  }

  return pills;
});

// Get human-readable field label
const getFieldLabel = (field: string): string => {
  const fieldConfig = searchController.facetConfig.fields.find(f => f.field === field);
  return fieldConfig?.label || field;
};

// Format filter values for display
const formatFilterValues = (field: string, values: (string | number)[]): string => {
  const formatted = values.map(v => getFacetValueLabel(field, String(v)));
  if (formatted.length <= 2) {
    return formatted.join(', ');
  }
  return `${formatted[0]}, ${formatted[1]} +${formatted.length - 2}`;
};

// Clear a specific field's filters
const clearFieldFilter = (field: string) => {
  searchController.setFilter(field, undefined);
};

// Handle query change
const handleQueryChange = (value: string) => {
  searchController.search(value);
  hasSearched.value = true;
};

// Handle explicit search (enter key)
const handleSearch = () => {
  searchController.search(searchController.query.value, true);
  hasSearched.value = true;
};

// Mark as searched when results come back
watch(
  () => searchController.results.value,
  (results) => {
    if (results.length > 0 || searchController.query.value || searchController.hasActiveFilters.value) {
      hasSearched.value = true;
    }
  },
);
</script>
