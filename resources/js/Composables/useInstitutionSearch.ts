/**
 * Institution Search Composable
 *
 * Uses shared search infrastructure with institution-specific functionality.
 */

import { ref, computed, nextTick, type ComputedRef } from 'vue';
import { useLocalStorage } from '@vueuse/core';
import { usePage } from '@inertiajs/vue3';

import type {
  InstitutionSearchFilters,
  InstitutionFacet,
  InstitutionSearchPreferences,
  InstitutionSearchController,
  SearchError,
} from '../Types/InstitutionSearchTypes';
import { InstitutionSearchService } from '../Services/InstitutionSearchService';

import { createTypesenseClients } from './useSearchClient';

import { useBaseSearch, type BaseSearchService } from '@/Shared/Search/composables/useBaseSearch';
import { FilterUtils } from '@/Shared/Search/services/FilterUtils';
import type { ProcessedSearchResult } from '@/Shared/Search/types';

// Re-export types for backward compatibility
export type {
  InstitutionSearchFilters,
  InstitutionFacet,
  InstitutionSearchPreferences,
};

// ============================================================================
// Types
// ============================================================================

/**
 * Extended preferences for institution search
 */
interface InstitutionPreferences extends InstitutionSearchPreferences {
  viewMode: 'grid' | 'list';
  recentSearches: string[];
}

// ============================================================================
// Service Adapter
// ============================================================================

/**
 * Adapter to make InstitutionSearchService compatible with BaseSearchService interface
 */
class InstitutionSearchServiceAdapter implements BaseSearchService<InstitutionSearchFilters, InstitutionFacet> {
  private service: InstitutionSearchService;
  private getLocale: () => string;

  constructor(service: InstitutionSearchService, getLocale: () => string) {
    this.service = service;
    this.getLocale = getLocale;
  }

  async performSearch(
    filters: InstitutionSearchFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number,
  ): Promise<ProcessedSearchResult<InstitutionFacet>> {
    const result = await this.service.performSearch(
      filters,
      perPage,
      isLoadMore,
      currentPage,
      this.getLocale(),
    );
    return {
      hits: result.hits,
      totalHits: result.totalHits,
      totalPages: result.totalPages,
      currentPage: result.currentPage,
      facets: result.facets,
    };
  }

  async loadInitialFacets(): Promise<InstitutionFacet[]> {
    return this.service.loadInitialFacets();
  }

  cancelCurrentSearch(): void {
    this.service.cancelCurrentSearch();
  }
}

// ============================================================================
// Composable
// ============================================================================

export const useInstitutionSearch = (): InstitutionSearchController => {
  // Extended preferences for institution search
  const institutionPreferences = useLocalStorage<InstitutionPreferences>('institution-search-preferences', {
    viewMode: 'grid', // Default to grid view for institutions
    recentSearches: [],
  });

  // Search client refs (for initialization)
  const searchClient = ref<any>(null);
  const typesenseClient = ref<any>(null);
  const institutionService = ref<InstitutionSearchService | null>(null);

  // Get current locale
  const getCurrentLocale = (): string => {
    const page = usePage();
    return (page.props.app as any)?.locale || 'lt';
  };

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

  // Initialize base search without service (will be set after client init)
  const baseSearch = useBaseSearch<InstitutionSearchFilters, InstitutionFacet, any>({
    defaultFilters,
    searchService: null, // Will be set after initialization
    filterKeyMapper,
    localStorageKey: 'institution-search-state',
    perPage: 24,
    debounceDelay: 200, // Faster debounce for institutions
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
      console.warn('Typesense not configured - institution search unavailable');
      return null;
    }

    try {
      // Get collection name from config (with staging prefix if applicable)
      const collectionName = typesenseConfig.collections?.public_institutions || 'public_institutions';

      // Build collection-specific search parameters
      const collectionSpecificSearchParameters: Record<string, any> = {};
      collectionSpecificSearchParameters[collectionName] = {
        query_by: 'name_lt,name_en,short_name_lt,short_name_en,alias',
        facet_by: ['tenant_shortname', 'type_slugs', 'has_contacts'].join(','),
        per_page: 24,
      };

      const clients = createTypesenseClients(typesenseConfig, {
        additionalSearchParameters: {
          query_by: 'name_lt,name_en,short_name_lt,short_name_en,alias',
          num_typos: 2,
          typo_tokens_threshold: 1,
          drop_tokens_threshold: 1,
          max_hits: 1000,
          per_page: 24,
          facet_by: ['tenant_shortname', 'type_slugs', 'has_contacts'].join(','),
          max_facet_values: 50,
          sort_by: 'duties_count:desc,updated_at:desc',
        },
        collectionSpecificSearchParameters,
      });

      searchClient.value = clients.searchClient;
      typesenseClient.value = clients.typesenseClient;

      // Create institution service and adapter
      institutionService.value = new InstitutionSearchService(typesenseClient.value, collectionName);
      const adapter = new InstitutionSearchServiceAdapter(institutionService.value, getCurrentLocale);

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
  // Institution-Specific Methods
  // ============================================================================

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

  // ============================================================================
  // Recent Searches (using institution preferences)
  // ============================================================================

  const clearRecentSearches = () => {
    institutionPreferences.value.recentSearches = [];
  };

  const removeRecentSearch = (searchToRemove: string) => {
    institutionPreferences.value.recentSearches = institutionPreferences.value.recentSearches.filter(
      s => s !== searchToRemove,
    );
  };

  // ============================================================================
  // Search State (matching original interface)
  // ============================================================================

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

  // ============================================================================
  // Return Controller (matching InstitutionSearchController interface)
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
    filters: baseSearch.filters as unknown as ComputedRef<InstitutionSearchFilters>,
    viewMode: computed(() => institutionPreferences.value.viewMode),
    recentSearches: computed(() => institutionPreferences.value.recentSearches),

    // Error handling
    searchError: baseSearch.error as ComputedRef<SearchError | null>,
    isOnline: baseSearch.isOnline,
    retryCount: baseSearch.retryCount,
    maxRetries: baseSearch.maxRetries,

    // Actions from base
    search: baseSearch.search,
    setFilter: baseSearch.setFilter as <K extends keyof InstitutionSearchFilters>(key: K, value: InstitutionSearchFilters[K]) => void,
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
    searchClient: computed(() => searchClient.value),
    initializeSearchClient,
  };
};
