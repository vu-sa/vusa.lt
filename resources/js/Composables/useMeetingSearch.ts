import { computed } from 'vue';
import { useLocalStorage } from '@vueuse/core';

import { useBaseSearch } from '@/Search/core/composables/useBaseSearch';
import { FilterUtils } from '@/Search/core/services/FilterUtils';
import { MeetingSearchService } from '@/Search/public/MeetingSearchService';
import { usePublicSearchService } from '@/Search/public/composables/usePublicSearchService';
import type { MeetingSearchFilters, MeetingFacet, MeetingSearchPreferences, MeetingSearchController } from '@/Types/MeetingSearchTypes';

export const useMeetingSearch = (): MeetingSearchController => {
  const meetingPreferences = useLocalStorage<MeetingSearchPreferences>('meeting-search-preferences', {
    viewMode: 'list',
    recentSearches: [],
  });

  // Default filters for meeting search
  const defaultFilters: MeetingSearchFilters = {
    query: '',
    tenants: [],
    institutionTypes: [],
    years: [],
    successRateRanges: [],
    dateRange: {},
    sort: 'relevance',
  };

  // Filter key mapper for meeting facets
  const filterKeyMapper = (field: string, filters: MeetingSearchFilters): string[] => {
    switch (field) {
      case 'tenant_shortname':
        return filters.tenants;
      case 'institution_type_title':
        return filters.institutionTypes;
      case 'year':
        return filters.years.map(String);
      default:
        return [];
    }
  };

  const baseSearch = useBaseSearch<MeetingSearchFilters, MeetingFacet, Record<string, unknown>>({
    defaultFilters,
    searchService: null,
    filterKeyMapper,
    localStorageKey: 'meeting-search-state',
    // Always holds a non-empty value, so the generic active-filter counter would
    // otherwise flag it even at its default.
    excludeFromFilterCount: ['sort'],
  });

  const { searchClient, initializeSearchClient } = usePublicSearchService(baseSearch, 'public_meetings', (client, collection, getLocale) =>
    new MeetingSearchService(client, collection, getLocale),
  );

  const toggleTenant = (tenantShortname: string) => {
    const current = baseSearch.filters.value.tenants;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      tenants: FilterUtils.toggleArrayValue(current, tenantShortname),
    };
    baseSearch.debouncedSearch();
  };

  const toggleInstitutionType = (type: string) => {
    const current = baseSearch.filters.value.institutionTypes;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      institutionTypes: FilterUtils.toggleArrayValue(current, type),
    };
    baseSearch.debouncedSearch();
  };

  const toggleYear = (year: number) => {
    const current = baseSearch.filters.value.years;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      years: FilterUtils.toggleArrayValue(current, year),
    };
    baseSearch.debouncedSearch();
  };

  const toggleSuccessRate = (range: string) => {
    const current = baseSearch.filters.value.successRateRanges;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      successRateRanges: FilterUtils.toggleArrayValue(current, range),
    };
    baseSearch.debouncedSearch();
  };

  const setDateRange = (dateRange: MeetingSearchFilters['dateRange']) => {
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      dateRange: {
        from: dateRange.from,
        to: dateRange.to,
        preset: dateRange.preset,
      },
    };
    baseSearch.debouncedSearch();
  };

  const setSortBy = (sort: MeetingSearchFilters['sort']) => {
    if (baseSearch.filters.value.sort === sort) return;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      sort,
    };
    baseSearch.debouncedSearch();
  };

  const setViewMode = (mode: 'list' | 'compact') => {
    meetingPreferences.value.viewMode = mode;
  };

  const clearFilters = () => {
    baseSearch.filters.value = {
      query: baseSearch.filters.value.query,
      tenants: [],
      institutionTypes: [],
      years: [],
      successRateRanges: [],
      dateRange: {},
      sort: 'relevance',
    };
    baseSearch.debouncedSearch();
  };

  const clearRecentSearches = () => {
    meetingPreferences.value.recentSearches = [];
  };

  const removeRecentSearch = (searchToRemove: string) => {
    meetingPreferences.value.recentSearches = meetingPreferences.value.recentSearches.filter(
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
    viewMode: meetingPreferences.value.viewMode,
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
    viewMode: computed(() => meetingPreferences.value.viewMode),
    recentSearches: computed(() => meetingPreferences.value.recentSearches),

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

    // Meeting-specific actions
    toggleTenant,
    toggleInstitutionType,
    toggleYear,
    toggleSuccessRate,
    setDateRange,
    setSortBy,
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
