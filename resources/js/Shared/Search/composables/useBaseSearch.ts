/**
 * useBaseSearch - Base search composable
 *
 * Provides common search functionality that can be extended by entity-specific
 * search composables. Handles:
 * - State management (status, results, facets, filters)
 * - Pagination
 * - Error handling with retry logic
 * - Debounced search
 * - Recent searches management
 * - Facet merging
 *
 * Entity-specific composables should:
 * 1. Define their own filter and result types
 * 2. Provide a search service implementation
 * 3. Configure filter-to-facet field mappings
 *
 * @example
 * ```typescript
 * const baseSearch = useBaseSearch({
 *   defaultFilters: { query: '', tenants: [] },
 *   searchService: myService,
 *   localStorageKey: 'my-search-preferences',
 *   filterKeyMapper: (field, filters) => {...}
 * })
 * ```
 */

import { ref, computed, watch, onUnmounted, shallowRef } from 'vue';
import { useLocalStorage, useOnline } from '@vueuse/core';
import { debounce } from 'lodash-es';

import type {
  SearchStatus,
  SearchError,
  BaseFacet,
  BaseSearchFilters,
  BaseSearchPreferences,
  ProcessedSearchResult,
  FacetConfig,
} from '../types';
import { FacetMerger, type FilterKeyMapper } from '../services/FacetMerger';
import { FilterUtils } from '../services/FilterUtils';
import { ErrorUtils, QueryUtils, RecentSearchManager } from '../services/SearchErrorUtils';

// ============================================================================
// Types
// ============================================================================

/**
 * Search service interface for base search
 */
export interface BaseSearchService<TFilters extends BaseSearchFilters, TFacet extends BaseFacet> {
  performSearch(
    filters: TFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number
  ): Promise<ProcessedSearchResult<TFacet>>;

  loadInitialFacets(): Promise<TFacet[]>;

  cancelCurrentSearch?(): void;
}

/**
 * Options for configuring useBaseSearch
 */
export interface UseBaseSearchOptions<
  TFilters extends BaseSearchFilters,
  TFacet extends BaseFacet,
  TResult = unknown,
> {
  /** Default filter values */
  defaultFilters: TFilters;

  /** Search service implementation */
  searchService: BaseSearchService<TFilters, TFacet> | null;

  /** Function to map facet fields to filter keys for selection */
  filterKeyMapper: FilterKeyMapper<TFilters>;

  /** LocalStorage key for preferences (if undefined, won't persist) */
  localStorageKey?: string;

  /** Items per page */
  perPage?: number;

  /** Debounce delay in ms */
  debounceDelay?: number;

  /** Max retry attempts for failed searches */
  maxRetries?: number;

  /** Whether to search on mount */
  searchOnMount?: boolean;

  /** Whether to load facets on mount */
  loadFacetsOnMount?: boolean;

  /** Facet configuration (for admin search) */
  facetConfig?: FacetConfig;
}

/**
 * Return type for useBaseSearch
 */
export interface BaseSearchReturn<
  TFilters extends BaseSearchFilters,
  TFacet extends BaseFacet,
  TResult = unknown,
> {
  // State refs
  status: ReturnType<typeof ref<SearchStatus>>;
  query: ReturnType<typeof ref<string>>;
  displayQuery: ReturnType<typeof ref<string>>;
  results: ReturnType<typeof shallowRef<TResult[]>>;
  facets: ReturnType<typeof shallowRef<TFacet[]>>;
  initialFacets: ReturnType<typeof shallowRef<TFacet[]>>;
  filters: ReturnType<typeof ref<TFilters>>;
  totalHits: ReturnType<typeof ref<number>>;
  error: ReturnType<typeof ref<SearchError | null>>;
  isOnline: ReturnType<typeof useOnline>;
  currentPage: ReturnType<typeof ref<number>>;
  totalPages: ReturnType<typeof ref<number>>;
  perPage: ReturnType<typeof ref<number>>;
  retryCount: ReturnType<typeof ref<number>>;
  maxRetries: number;

  // Computed
  isSearching: ReturnType<typeof computed<boolean>>;
  isLoadingFacets: ReturnType<typeof computed<boolean>>;
  isLoadingMore: ReturnType<typeof computed<boolean>>;
  hasResults: ReturnType<typeof computed<boolean>>;
  hasActiveFilters: ReturnType<typeof computed<boolean>>;
  hasMoreResults: ReturnType<typeof computed<boolean>>;
  activeFilterCount: ReturnType<typeof computed<number>>;
  mergedFacets: ReturnType<typeof computed<TFacet[]>>;

  // Preferences
  preferences: ReturnType<typeof useLocalStorage<BaseSearchPreferences>>;

  // Methods
  search: (query: string, immediate?: boolean) => void;
  performSearch: (isLoadMore?: boolean, attempt?: number) => Promise<void>;
  debouncedSearch: ReturnType<typeof debounce>;
  loadMore: () => void;
  refresh: () => void;

  setFilter: <K extends keyof TFilters>(key: K, value: TFilters[K]) => void;
  toggleFilter: (field: string, value: string | number) => void;
  clearFilters: () => void;

  retrySearch: () => void;
  clearError: () => void;
  handleSearchError: (error: unknown, context: string) => void;

  loadInitialFacets: () => Promise<void>;
  setSearchService: (service: BaseSearchService<TFilters, TFacet>) => void;
}

// ============================================================================
// Composable
// ============================================================================

export function useBaseSearch<
  TFilters extends BaseSearchFilters,
  TFacet extends BaseFacet,
  TResult = unknown,
>(options: UseBaseSearchOptions<TFilters, TFacet, TResult>): BaseSearchReturn<TFilters, TFacet, TResult> {
  const {
    defaultFilters,
    filterKeyMapper,
    localStorageKey,
    perPage: defaultPerPage = 24,
    debounceDelay = 300,
    maxRetries = 3,
    searchOnMount = false,
    loadFacetsOnMount = false,
    facetConfig,
  } = options;

  // ============================================================================
  // State
  // ============================================================================

  // Search service (can be set later)
  const searchService = ref<BaseSearchService<TFilters, TFacet> | null>(options.searchService);

  // Core state
  const status = ref<SearchStatus>('idle');
  const displayQuery = ref(''); // What user sees in input
  const query = ref(''); // What we search for (may be '*' for initial load)

  // Results - use shallowRef for performance with large arrays
  const results = shallowRef<TResult[]>([]);
  const totalHits = ref(0);
  const facets = shallowRef<TFacet[]>([]);
  const initialFacets = shallowRef<TFacet[]>([]);
  const initialFacetsLoaded = ref(false);

  // Filters
  const filters = ref<TFilters>({ ...defaultFilters }) as ReturnType<typeof ref<TFilters>>;

  // Pagination
  const currentPage = ref(0);
  const totalPages = ref(0);
  const perPage = ref(defaultPerPage);

  // Error handling
  const error = ref<SearchError | null>(null);
  const isOnline = useOnline();
  const retryCount = ref(0);

  // Preferences (persisted to localStorage if key provided)
  const preferences = localStorageKey
    ? useLocalStorage<BaseSearchPreferences>(localStorageKey, {
        recentSearches: [],
        viewMode: 'list',
      })
    : ref<BaseSearchPreferences>({
        recentSearches: [],
        viewMode: 'list',
      });

  // ============================================================================
  // Computed
  // ============================================================================

  const isSearching = computed(() => status.value === 'searching');
  const isLoadingFacets = computed(() => status.value === 'loading-facets');
  const isLoadingMore = computed(() => status.value === 'loading-more');
  const hasResults = computed(() => results.value.length > 0);
  const hasMoreResults = computed(() => currentPage.value < totalPages.value);

  const hasActiveFilters = computed(() => {
    if (facetConfig) {
      return FilterUtils.hasActiveFilters(filters.value, ['query']);
    }
    return FilterUtils.hasActiveFilters(filters.value, ['query']);
  });

  const activeFilterCount = computed(() => {
    return FilterUtils.countActiveFilters(filters.value, ['query']);
  });

  // Merged facets with selection state
  const mergedFacets = computed(() => {
    return FacetMerger.mergeFacets(
      initialFacets.value,
      facets.value,
      filters.value,
      filterKeyMapper,
    );
  });

  // ============================================================================
  // Error Handling
  // ============================================================================

  const clearError = () => {
    error.value = null;
    retryCount.value = 0;
    if (status.value === 'error') {
      status.value = 'idle';
    }
  };

  const handleSearchError = (err: unknown, context: string) => {
    console.error(`Search error in ${context}:`, err);

    // Don't set error for aborted requests
    if (ErrorUtils.isAbortError(err)) {
      return;
    }

    error.value = ErrorUtils.fromError(err, context);
    status.value = 'error';
  };

  // ============================================================================
  // Search Methods
  // ============================================================================

  const performSearch = async (isLoadMore = false, attempt = 1) => {
    // Clear previous errors on new search attempts
    if (!isLoadMore && attempt === 1) {
      clearError();
    }

    if (!searchService.value) {
      handleSearchError(new Error('Search service not initialized'), 'service initialization');
      return;
    }

    // Check network connectivity
    if (!isOnline.value) {
      error.value = ErrorUtils.createSearchError(
        'network',
        'No internet connection',
        'Check your internet connection and try again',
        'OFFLINE',
        true,
      );
      return;
    }

    // Validate search
    const shouldSearch = QueryUtils.shouldSearch(
      filters.value.query,
      hasActiveFilters.value,
    );

    if (!shouldSearch) {
      results.value = [];
      totalHits.value = 0;
      facets.value = [];
      currentPage.value = 0;
      totalPages.value = 0;
      status.value = 'idle';
      return;
    }

    // Set loading state
    status.value = isLoadMore ? 'loading-more' : 'searching';

    try {
      const result = await searchService.value.performSearch(
        filters.value,
        perPage.value,
        isLoadMore,
        currentPage.value,
      );

      // Update state with results
      if (isLoadMore) {
        results.value = [...results.value, ...result.hits] as TResult[];
      }
      else {
        results.value = result.hits as TResult[];
      }

      totalHits.value = result.totalHits;
      totalPages.value = result.totalPages;
      currentPage.value = result.currentPage;
      facets.value = result.facets;

      // Add to recent searches
      if (QueryUtils.isValidQuery(displayQuery.value)) {
        preferences.value = {
          ...preferences.value,
          recentSearches: RecentSearchManager.addToRecentSearches(
            preferences.value.recentSearches,
            displayQuery.value,
          ),
        };
      }

      // Reset retry count on success
      retryCount.value = 0;
      status.value = 'idle';
    }
    catch (err) {
      if (!ErrorUtils.isAbortError(err)) {
        handleSearchError(err, `search attempt ${attempt}`);

        // Retry logic
        if (ErrorUtils.shouldRetry(err, attempt, maxRetries)) {
          console.log(`Retrying search, attempt ${attempt + 1}/${maxRetries}`);
          retryCount.value = attempt;

          const delay = ErrorUtils.getRetryDelay(attempt);
          setTimeout(() => {
            performSearch(isLoadMore, attempt + 1);
          }, delay);
          return;
        }
      }
      status.value = 'idle';
    }
  };

  // Debounced search
  const debouncedSearch = debounce(() => {
    if (status.value === 'idle' || status.value === 'error') {
      performSearch(false);
    }
  }, debounceDelay);

  const search = (newQuery: string, immediate = false) => {
    // Handle wildcard for initial load
    if (newQuery === '*') {
      query.value = '*';
      filters.value = { ...filters.value, query: '*' };
      displayQuery.value = '';
    }
    else {
      displayQuery.value = newQuery;
      query.value = newQuery;
      filters.value = { ...filters.value, query: newQuery };
    }

    if (immediate) {
      performSearch(false);
    }
    else {
      debouncedSearch();
    }
  };

  const loadMore = () => {
    if (status.value !== 'loading-more' && hasMoreResults.value) {
      performSearch(true);
    }
  };

  const refresh = () => {
    performSearch(false);
  };

  // ============================================================================
  // Filter Methods
  // ============================================================================

  const setFilter = <K extends keyof TFilters>(key: K, value: TFilters[K]) => {
    filters.value = { ...filters.value, [key]: value };
    debouncedSearch();
  };

  const toggleFilter = (field: string, value: string | number) => {
    const currentValue = filters.value[field as keyof TFilters];

    if (Array.isArray(currentValue)) {
      const newValue = FilterUtils.toggleArrayValue(currentValue, value);
      filters.value = { ...filters.value, [field]: newValue };
    }
    else {
      // For non-array fields, set or clear
      filters.value = {
        ...filters.value,
        [field]: currentValue === value ? undefined : value,
      };
    }

    debouncedSearch();
  };

  const clearFilters = () => {
    filters.value = FilterUtils.clearFilters(filters.value, defaultFilters);
    debouncedSearch();
  };

  // ============================================================================
  // Retry
  // ============================================================================

  const retrySearch = () => {
    if (error.value?.retryable) {
      clearError();
      performSearch(false, 1);
    }
  };

  // ============================================================================
  // Initial Facets
  // ============================================================================

  const loadInitialFacets = async () => {
    if (!searchService.value || initialFacetsLoaded.value) {
      return;
    }

    status.value = 'loading-facets';

    try {
      const facetResults = await searchService.value.loadInitialFacets();
      initialFacets.value = facetResults;
      initialFacetsLoaded.value = true;

      // Use initial facets as default if no current facets
      if (facets.value.length === 0) {
        facets.value = [...initialFacets.value];
      }
    }
    catch (err) {
      console.error('Failed to load initial facets:', err);
      initialFacets.value = [];
    }
    finally {
      status.value = 'idle';
    }
  };

  // ============================================================================
  // Service Management
  // ============================================================================

  const setSearchService = (service: BaseSearchService<TFilters, TFacet>) => {
    searchService.value = service;
  };

  // ============================================================================
  // Watchers
  // ============================================================================

  // Watch for external filter query changes
  watch(
    () => filters.value.query,
    (newQuery) => {
      if (newQuery !== '*') {
        displayQuery.value = newQuery;
      }
      query.value = newQuery;
    },
  );

  // ============================================================================
  // Lifecycle
  // ============================================================================

  onUnmounted(() => {
    // Cancel ongoing searches
    if (searchService.value?.cancelCurrentSearch) {
      searchService.value.cancelCurrentSearch();
    }
    debouncedSearch.cancel();
  });

  // ============================================================================
  // Return
  // ============================================================================

  return {
    // State
    status,
    query,
    displayQuery,
    results,
    facets,
    initialFacets,
    filters,
    totalHits,
    error,
    isOnline,
    currentPage,
    totalPages,
    perPage,
    retryCount,
    maxRetries,

    // Computed
    isSearching,
    isLoadingFacets,
    isLoadingMore,
    hasResults,
    hasActiveFilters,
    hasMoreResults,
    activeFilterCount,
    mergedFacets,

    // Preferences
    preferences,

    // Search methods
    search,
    performSearch,
    debouncedSearch,
    loadMore,
    refresh,

    // Filter methods
    setFilter,
    toggleFilter,
    clearFilters,

    // Error handling
    retrySearch,
    clearError,
    handleSearchError,

    // Facets
    loadInitialFacets,
    setSearchService,
  };
}
