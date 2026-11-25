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

import { ref, computed, watch, onScopeDispose, shallowRef, type Ref } from 'vue';
import { useLocalStorage, useOnline, useDebounceFn } from '@vueuse/core';

import type {
  SearchStatus,
  SearchError,
  BaseFacet,
  BaseSearchFilters,
  BaseSearchPreferences,
  ProcessedSearchResult,
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
export interface BaseSearchService<TFilters extends BaseSearchFilters, TFacet extends BaseFacet, TResult = unknown> {
  performSearch(
    filters: TFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number
  ): Promise<ProcessedSearchResult<TFacet, TResult>>;

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
  searchService: BaseSearchService<TFilters, TFacet, TResult> | null;

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

  /**
   * Filter keys to leave out of `hasActiveFilters`/`activeFilterCount`, on top of `query`.
   * For fields that always hold a non-empty value (a sort mode, a status toggle with a
   * sensible default) — `FilterUtils`'s generic string/number check would otherwise count
   * them as "active" even at their default value.
   */
  excludeFromFilterCount?: string[];
}

// ============================================================================
// Composable
// ============================================================================

export function useBaseSearch<
  TFilters extends BaseSearchFilters,
  TFacet extends BaseFacet,
  TResult = unknown,
>(options: UseBaseSearchOptions<TFilters, TFacet, TResult>) {
  const {
    defaultFilters,
    filterKeyMapper,
    localStorageKey,
    perPage: defaultPerPage = 24,
    debounceDelay = 300,
    maxRetries = 3,
    excludeFromFilterCount = [],
  } = options;

  const filterCountExcludeKeys = ['query', ...excludeFromFilterCount];

  // ============================================================================
  // State
  // ============================================================================

  // Search service (can be set later)
  const searchService = shallowRef<BaseSearchService<TFilters, TFacet, TResult> | null>(options.searchService);

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
  const filters = ref<TFilters>({ ...defaultFilters }) as Ref<TFilters>;

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
    return FilterUtils.hasActiveFilters(filters.value, filterCountExcludeKeys);
  });

  const activeFilterCount = computed(() => {
    return FilterUtils.countActiveFilters(filters.value, filterCountExcludeKeys);
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

  let searchGeneration = 0;
  const performSearch = async (isLoadMore = false, attempt = 1) => {
    const generation = ++searchGeneration;
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
      status.value = 'error';
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

      if (generation !== searchGeneration) return;

      // Update state with results
      if (isLoadMore) {
        results.value = [...results.value, ...result.hits];
      }
      else {
        results.value = result.hits;
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
      if (generation !== searchGeneration) return;
      if (!ErrorUtils.isAbortError(err)) {
        handleSearchError(err, `search attempt ${attempt}`);

        // Retry logic
        if (ErrorUtils.shouldRetry(err, attempt, maxRetries)) {
          retryCount.value = attempt;

          const delay = ErrorUtils.getRetryDelay(attempt);
          setTimeout(() => {
            if (generation === searchGeneration) performSearch(isLoadMore, attempt + 1);
          }, delay);
          return;
        }
      }
      status.value = 'idle';
    }
  };

  // Debounced search
  const debouncedSearch = useDebounceFn(() => {
    performSearch(false);
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
    if (status.value === 'idle' && hasMoreResults.value) {
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

    const generation = searchGeneration;
    const wasIdle = status.value === 'idle';
    if (wasIdle) status.value = 'loading-facets';

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
      if (wasIdle && generation === searchGeneration && status.value === 'loading-facets') {
        status.value = 'idle';
      }
    }
  };

  // ============================================================================
  // Service Management
  // ============================================================================

  const setSearchService = (service: BaseSearchService<TFilters, TFacet, TResult>) => {
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

  onScopeDispose(() => {
    searchGeneration++;
    searchService.value?.cancelCurrentSearch?.();
    debouncedSearch.cancel();
  }, true);

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

    // Cancellation
    cancelPendingSearch: () => debouncedSearch.cancel(),
  };
}
