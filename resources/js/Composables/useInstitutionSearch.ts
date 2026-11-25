import { computed } from 'vue';
import { useLocalStorage } from '@vueuse/core';

import { useBaseSearch } from '@/Search/core/composables/useBaseSearch';
import { FilterUtils } from '@/Search/core/services/FilterUtils';
import type { TypesenseClient } from '@/Search/core/services/SearchClientFactory';
import { InstitutionSearchService, type RawInstitutionDocument } from '@/Search/public/InstitutionSearchService';
import { usePublicSearchService } from '@/Search/public/composables/usePublicSearchService';
import type { InstitutionSearchFilters, InstitutionFacet, InstitutionSearchPreferences, InstitutionSearchController } from '@/Types/InstitutionSearchTypes';

export type { InstitutionSearchPreferences };

export const useInstitutionSearch = (options: { typeDescendants?: Record<string, string[]> } = {}): InstitutionSearchController => {
  const institutionPreferences = useLocalStorage<InstitutionSearchPreferences>('institution-search-preferences', {
    viewMode: 'grid', // Default to grid view for institutions
    recentSearches: [],
  });

  // Default filters for institution search
  const defaultFilters: InstitutionSearchFilters = {
    query: '',
    tenants: [],
    types: [],
    hasContacts: null,
  };

  // Filter key mapper for institution facets
  const filterKeyMapper = (field: string, filters: InstitutionSearchFilters): string[] => {
    switch (field) {
      case 'tenant_shortname':
        return filters.tenants;
      case 'type_slugs':
        return filters.types;
      default:
        return [];
    }
  };

  const baseSearch = useBaseSearch<InstitutionSearchFilters, InstitutionFacet, Record<string, unknown>>({
    defaultFilters,
    searchService: null,
    filterKeyMapper,
    localStorageKey: 'institution-search-state',
    debounceDelay: 200, // Faster debounce for institutions
  });

  const { searchClient, initializeSearchClient } = usePublicSearchService(
    baseSearch,
    'public_institutions',
    (client: TypesenseClient<RawInstitutionDocument>, collection, getLocale) =>
      new InstitutionSearchService(client, collection, options.typeDescendants, getLocale),
  );

  const toggleTenant = (tenantShortname: string) => {
    const current = baseSearch.filters.value.tenants;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      tenants: FilterUtils.toggleArrayValue(current, tenantShortname),
    };
    baseSearch.debouncedSearch();
  };

  const toggleType = (typeSlug: string) => {
    const current = baseSearch.filters.value.types;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      types: FilterUtils.toggleArrayValue(current, typeSlug),
    };
    baseSearch.debouncedSearch();
  };

  const setHasContacts = (value: boolean | null) => {
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      hasContacts: value,
    };
    baseSearch.debouncedSearch();
  };

  const setViewMode = (mode: 'grid' | 'list') => {
    institutionPreferences.value.viewMode = mode;
  };

  const clearFilters = () => {
    baseSearch.filters.value = {
      query: baseSearch.filters.value.query,
      tenants: [],
      types: [],
      hasContacts: null,
    };
    baseSearch.debouncedSearch();
  };

  const clearRecentSearches = () => {
    institutionPreferences.value.recentSearches = [];
  };

  const removeRecentSearch = (searchToRemove: string) => {
    institutionPreferences.value.recentSearches = institutionPreferences.value.recentSearches.filter(
      s => s !== searchToRemove,
    );
  };

  const searchState = computed(() => ({
    isSearching: baseSearch.status.value === 'searching',
    hasResults: baseSearch.hasResults.value,
    totalHits: baseSearch.totalHits.value,
    query: baseSearch.displayQuery.value,
    filters: baseSearch.filters.value,
    facets: baseSearch.facets.value,
    results: baseSearch.results.value,
    viewMode: institutionPreferences.value.viewMode,
    error: baseSearch.error.value,
    isOnline: baseSearch.isOnline.value,
    status: baseSearch.status.value,
  }));

  return {
    // State from base
    searchState,
    isSearching: baseSearch.isSearching,
    isLoadingFacets: baseSearch.isLoadingFacets,
    isLoadingMore: baseSearch.isLoadingMore,
    hasResults: baseSearch.hasResults,
    hasActiveFilters: baseSearch.hasActiveFilters,
    hasMoreResults: baseSearch.hasMoreResults,
    totalHits: baseSearch.totalHits,
    results: baseSearch.results,
    facets: baseSearch.mergedFacets, // Use merged facets
    filters: baseSearch.filters,
    viewMode: computed(() => institutionPreferences.value.viewMode),
    recentSearches: computed(() => institutionPreferences.value.recentSearches),

    // Error handling
    searchError: baseSearch.error,
    isOnline: baseSearch.isOnline,
    retryCount: baseSearch.retryCount,
    maxRetries: baseSearch.maxRetries,

    // Actions from base
    search: baseSearch.search,
    setFilter: baseSearch.setFilter,
    loadMore: baseSearch.loadMore,
    retrySearch: baseSearch.retrySearch,
    clearError: baseSearch.clearError,
    loadInitialFacets: baseSearch.loadInitialFacets,

    // Institution-specific actions
    toggleTenant,
    toggleType,
    setHasContacts,
    setViewMode,
    clearFilters,
    clearRecentSearches,
    removeRecentSearch,

    // Internal
    searchClient,
    initializeSearchClient,
    cancelPendingSearch: baseSearch.cancelPendingSearch,
  };
};
