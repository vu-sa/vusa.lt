/**
 * Meeting Search Composable V2
 *
 * Refactored version using shared search infrastructure.
 * This composable extends useBaseSearch with meeting-specific functionality.
 */

import { ref, computed, nextTick, type Ref } from 'vue';
import { useLocalStorage } from '@vueuse/core';
import { usePage } from '@inertiajs/vue3';

import type {
  MeetingSearchFilters,
  MeetingFacet,
  MeetingSearchPreferences,
  MeetingSearchController,
} from '../Types/MeetingSearchTypes';
import { MeetingSearchService } from '../Services/MeetingSearchService';

import { createTypesenseClients } from './useSearchClient';

import { useBaseSearch, type BaseSearchService } from '@/Shared/Search/composables/useBaseSearch';
import { FilterUtils } from '@/Shared/Search/services/FilterUtils';
import type { ProcessedSearchResult } from '@/Shared/Search/types';

// Re-export types for backward compatibility
export type {
  MeetingSearchFilters,
  MeetingFacet,
};

// ============================================================================
// Types
// ============================================================================

/**
 * Extended preferences for meeting search
 */
interface MeetingPreferences extends MeetingSearchPreferences {
  viewMode: 'list' | 'compact';
  recentSearches: string[];
}

// ============================================================================
// Service Adapter
// ============================================================================

/**
 * Adapter to make MeetingSearchService compatible with BaseSearchService interface
 */
class MeetingSearchServiceAdapter implements BaseSearchService<MeetingSearchFilters, MeetingFacet> {
  private service: MeetingSearchService;

  constructor(service: MeetingSearchService) {
    this.service = service;
  }

  async performSearch(
    filters: MeetingSearchFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number,
  ): Promise<ProcessedSearchResult<MeetingFacet>> {
    const result = await this.service.performSearch(filters, perPage, isLoadMore, currentPage);
    return {
      hits: result.hits,
      totalHits: result.totalHits,
      totalPages: result.totalPages,
      currentPage: result.currentPage,
      facets: result.facets,
    };
  }

  async loadInitialFacets(): Promise<MeetingFacet[]> {
    return this.service.loadInitialFacets();
  }

  cancelCurrentSearch(): void {
    this.service.cancelCurrentSearch();
  }
}

// ============================================================================
// Composable
// ============================================================================

export const useMeetingSearch = (): MeetingSearchController => {
  // Extended preferences for meeting search
  const meetingPreferences = useLocalStorage<MeetingPreferences>('meeting-search-preferences', {
    viewMode: 'list',
    recentSearches: [],
  });

  // Search client refs (for initialization)
  const searchClient = ref<any>(null);
  const typesenseClient = ref<any>(null);
  const meetingService = ref<MeetingSearchService | null>(null);

  // Default filters for meeting search
  const defaultFilters: MeetingSearchFilters = {
    query: '',
    tenants: [],
    institutionTypes: [],
    years: [],
    successRateRanges: [],
    dateRange: {},
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

  // Initialize base search without service (will be set after client init)
  const baseSearch = useBaseSearch<MeetingSearchFilters, MeetingFacet, any>({
    defaultFilters,
    searchService: null, // Will be set after initialization
    filterKeyMapper,
    localStorageKey: 'meeting-search-state',
    perPage: 24,
    debounceDelay: 300,
    maxRetries: 3,
    searchOnMount: false,
    loadFacetsOnMount: false,
  });

  // ============================================================================
  // Client Initialization
  // ============================================================================

  const initializeSearchClient = async () => {
    const page = usePage();
    const typesenseConfig = page.props.typesenseConfig as any;

    if (!typesenseConfig?.apiKey) {
      console.warn('Typesense not configured - meeting search unavailable');
      return null;
    }

    try {
      const locale = page.props.app.locale || 'lt';
      const institutionNameField = `institution_name_${locale}`;
      const collectionName = typesenseConfig.collections?.public_meetings || 'public_meetings';

      // Build collection-specific search parameters
      const collectionSpecificSearchParameters: Record<string, any> = {};
      collectionSpecificSearchParameters[collectionName] = {
        query_by: `title,description,${institutionNameField}`,
        facet_by: ['year', 'month', 'tenant_shortname'].join(','),
        per_page: 24,
      };

      const clients = createTypesenseClients(typesenseConfig, {
        additionalSearchParameters: {
          query_by: `title,description,${institutionNameField}`,
          num_typos: 2,
          typo_tokens_threshold: 1,
          drop_tokens_threshold: 1,
          max_hits: 1000,
          per_page: 24,
          facet_by: ['year', 'month', 'tenant_shortname'].join(','),
          max_facet_values: 50,
          sort_by: 'start_time:desc',
        },
        collectionSpecificSearchParameters,
      });

      searchClient.value = clients.searchClient;
      typesenseClient.value = clients.typesenseClient;

      // Create meeting service and adapter
      meetingService.value = new MeetingSearchService(typesenseClient.value, collectionName);
      const adapter = new MeetingSearchServiceAdapter(meetingService.value);

      // Set the service on base search
      baseSearch.setSearchService(adapter);

      // Load initial facets
      await nextTick();
      await baseSearch.loadInitialFacets();

      return clients.searchClient;
    }
    catch (error) {
      console.error('Failed to initialize Typesense clients:', error);
      return null;
    }
  };

  // ============================================================================
  // Meeting-Specific Methods
  // ============================================================================

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

  const setDateRange = (dateRange: { from?: string; to?: string; preset?: string }) => {
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
    };
    baseSearch.debouncedSearch();
  };

  // ============================================================================
  // Recent Searches (using meeting preferences)
  // ============================================================================

  const clearRecentSearches = () => {
    meetingPreferences.value.recentSearches = [];
  };

  const removeRecentSearch = (searchToRemove: string) => {
    meetingPreferences.value.recentSearches = meetingPreferences.value.recentSearches.filter(
      s => s !== searchToRemove,
    );
  };

  // ============================================================================
  // Search State
  // ============================================================================

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

  // ============================================================================
  // Return Controller
  // ============================================================================

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
    filters: baseSearch.filters as unknown as Ref<MeetingSearchFilters>,
    viewMode: computed(() => meetingPreferences.value.viewMode),
    recentSearches: computed(() => meetingPreferences.value.recentSearches),

    // Error handling
    searchError: baseSearch.error,
    isOnline: baseSearch.isOnline,
    retryCount: baseSearch.retryCount,
    maxRetries: baseSearch.maxRetries,

    // Actions from base
    search: baseSearch.search,
    setFilter: baseSearch.setFilter as <K extends keyof MeetingSearchFilters>(key: K, value: MeetingSearchFilters[K]) => void,
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
    setViewMode,
    clearFilters,
    clearRecentSearches,
    removeRecentSearch,

    // Internal
    searchClient: computed(() => searchClient.value),
    initializeSearchClient,
  };
};
