/**
 * Admin Collection Search Composable
 *
 * Main composable for faceted search on a single admin collection.
 * Handles state management, URL sync, facet merging, and search operations.
 */

import { ref, computed, watch, onMounted, onUnmounted, shallowRef, toValue, type MaybeRefOrGetter } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useUrlSearchParams, useDebounceFn } from '@vueuse/core';

import type {
  AdminCollection,
  AdminFacet,
  AdminSearchFilters,
  AdminSearchStatus,
  AdminSearchError,
  SortOption,
} from '../Types/AdminSearchTypes';
import {
  buildFilterString,
  parseFacets,
  filtersToUrlParams,
  urlParamsToFilters,
  countActiveFilters,
  hasActiveFilters as checkActiveFilters,
  clearFilters as clearAllFilters,
  toggleFilterValue,
  toggleNumericFilterValue,
} from '../Services/AdminSearchService';
import { mergeFacets, sortFacetsByConfig } from '../Services/AdminFacetMerger';
import { getCollectionFacetConfig, getCollectionSortOptions, resolveSortValue, RELEVANCE_SORT_VALUE } from '../Config/collectionFacetConfig';

import { provideFacetSearch } from '@/Shared/Search/facets';
import { useAdminSearch } from '@/Composables/useAdminSearch';
import { useCollectionFilterMemory } from '@/Composables/collectionFilterMemory';

/** Bounds the replay of `?pages=`, so a hand-edited URL cannot fan out into hundreds of requests. */
const MAX_RESTORED_PAGES = 10;

export interface UseAdminCollectionSearchOptions {
  collection: AdminCollection;
  /** Initial query from URL or default */
  initialQuery?: string;
  /** Whether to load initial facets on mount */
  loadFacetsOnMount?: boolean;
  /** Whether to perform initial search on mount */
  searchOnMount?: boolean;
  /** Whether to sync state to URL */
  syncToUrl?: boolean;
  /** URL query keys to preserve when syncing state to URL (e.g. ['tab']) */
  preserveUrlKeys?: string[];
  /** Debounce delay for search in ms */
  debounceMs?: number;
  /** Results per page */
  perPage?: number;
  /**
   * Always-on Typesense `filter_by` clause ANDed with the facet filters and
   * applied to the initial facet universe too. Used to scope a collection to a
   * caller-defined subset (e.g. the institution picker restricted to the
   * duty-assignable tenants). Leave undefined for the unrestricted collection.
   * A ref or getter re-runs the search when it changes (IndexInstitution's "Sekamos").
   */
  baseFilterBy?: MaybeRefOrGetter<string | undefined>;
  /**
   * Filters a first visit starts with (e.g. the user's own padaliniai). Ordinary, clearable
   * filters — applied only when the URL carries no query or filter of its own.
   */
  defaultFilters?: Record<string, string[]>;
}

export function useAdminCollectionSearch(options: UseAdminCollectionSearchOptions) {
  const {
    collection,
    initialQuery = '',
    loadFacetsOnMount = true,
    searchOnMount = true,
    syncToUrl = true,
    preserveUrlKeys = [],
    debounceMs = 300,
    perPage = 24,
  } = options;
  const page = usePage();
  const baseFilterBy = computed(() => toValue(options.baseFilterBy) || undefined);

  // Get the facet config for this collection
  const configuredFacets = getCollectionFacetConfig(collection);
  if (!configuredFacets) {
    throw new Error(`No facet configuration found for collection: ${collection}`);
  }
  const facetConfig = { ...configuredFacets, fields: [...configuredFacets.fields] };

  // Get the base admin search composable
  const adminSearch = useAdminSearch();

  // URL params for state persistence
  const urlParams = syncToUrl ? useUrlSearchParams('history') : ref({});
  const filterMemory = syncToUrl
    ? useCollectionFilterMemory([...facetConfig.fields.map(field => field.field), 'sort'], ['q'])
    : null;

  // State
  const status = ref<AdminSearchStatus>('idle');
  const query = ref(initialQuery);
  const filters = ref<AdminSearchFilters>({ query: initialQuery });
  // An active query defaults to relevance ranking; an empty query browses by date.
  const sortBy = ref(initialQuery.trim() !== '' ? RELEVANCE_SORT_VALUE : facetConfig.defaultSortBy);
  // Tracks whether the user explicitly picked a sort. Until they do, the query
  // state drives the default sort (relevance when querying, date when browsing).
  const isSortUserSelected = ref(false);
  const currentPage = ref(1);
  // Pages the URL says were loaded before leaving (`?pages=3`); replayed after the first search
  // so back navigation lands on the same rows. Consumed once.
  let pagesToRestore = 1;

  // Results state (using shallowRef for performance with large arrays)
  const results = shallowRef<unknown[]>([]);
  const totalHits = ref(0);
  const totalPages = ref(0);

  // Facets state
  const facets = shallowRef<AdminFacet[]>([]);
  const initialFacets = shallowRef<AdminFacet[]>([]);
  const initialFacetsLoaded = ref(false);
  let initialFacetsPromise: Promise<void> | null = null;

  // Error state
  const error = ref<AdminSearchError | null>(null);

  // Sort options for this collection
  const sortOptions = computed<SortOption[]>(() => getCollectionSortOptions(collection));

  // Computed properties
  const isSearching = computed(() => status.value === 'searching');
  const isLoadingFacets = computed(() => status.value === 'loading-facets');
  const isLoadingMore = computed(() => status.value === 'loading-more');
  const hasResults = computed(() => results.value.length > 0);
  const hasMoreResults = computed(() => currentPage.value < totalPages.value);
  const activeFilterCount = computed(() => countActiveFilters(filters.value, facetConfig));
  const hasActiveFilters = computed(() => checkActiveFilters(filters.value, facetConfig));

  // Merged facets (initial + current with selection state)
  const mergedFacets = computed(() => {
    const merged = mergeFacets(initialFacets.value, facets.value, filters.value, facetConfig);
    return sortFacetsByConfig(merged, facetConfig);
  });

  // Search ready state
  const isReady = computed(() => adminSearch.isReady.value && initialFacetsLoaded.value);

  /**
   * Clear error state
   */
  const clearError = () => {
    error.value = null;
  };

  /**
   * Validate collection access and API key
   * @returns true if access is valid, false otherwise (sets error state)
   */
  const validateCollectionAccess = (): boolean => {
    if (!adminSearch.hasCollectionAccess(collection) || !adminSearch.getCollectionApiKey(collection)) {
      error.value = {
        type: 'auth',
        message: 'No access to this collection',
        userMessage: 'Neturi prieigos prie šios paieškos.',
        retryable: false,
      };
      return false;
    }
    return true;
  };

  /**
   * Load initial facets (all available filter options)
   */
  const loadInitialFacets = (): Promise<void> => {
    if (initialFacetsPromise) return initialFacetsPromise;
    if (initialFacetsLoaded.value) return Promise.resolve();
    initialFacetsPromise = fetchInitialFacets().finally(() => {
      initialFacetsPromise = null;
    });
    return initialFacetsPromise;
  };

  const fetchInitialFacets = async () => {
    if (!validateCollectionAccess()) return;

    status.value = 'loading-facets';

    try {
      const rawFacets = await adminSearch.loadInitialFacets(collection, facetConfig.facetBy, {
        queryBy: facetConfig.queryBy,
        filterBy: baseFilterBy.value,
      });

      initialFacets.value = parseFacets(rawFacets, facetConfig, filters.value);
      initialFacetsLoaded.value = true;

      // If no current facets, use initial as default
      if (facets.value.length === 0) {
        facets.value = [...initialFacets.value];
      }
    }
    catch (err) {
      console.error('Failed to load initial facets:', err);
      error.value = {
        type: 'server',
        message: err instanceof Error ? err.message : 'Unknown error',
        userMessage: 'Nepavyko įkelti filtrų. Bandyk dar kartą.',
        retryable: true,
      };
    }
    finally {
      status.value = 'idle';
    }
  };

  /**
   * Perform search with current filters
   */
  provideFacetSearch(async (field, text, signal) => {
    const filterBy = [baseFilterBy.value, buildFilterString({ ...filters.value, [field]: undefined }, facetConfig)].filter(Boolean).join(' && ');
    return adminSearch.searchFacetValues(collection, field, text, { query: query.value || '*', filterBy, queryBy: facetConfig.queryBy, signal });
  });

  let searchGeneration = 0;
  const executeSearch = async (isLoadMore = false) => {
    const generation = ++searchGeneration;
    status.value = isLoadMore ? 'loading-more' : 'searching';
    if (initialFacetsPromise) await initialFacetsPromise;
    if (generation !== searchGeneration) return;
    // Clear previous error
    if (!isLoadMore) {
      clearError();
    }

    // Build filter string from current filters, ANDed with any always-on base filter.
    const filterString = [baseFilterBy.value, buildFilterString(filters.value, facetConfig)]
      .filter(Boolean)
      .join(' && ');

    // Set loading state
    if (isLoadMore) {
      status.value = 'loading-more';
      currentPage.value += 1;
    }
    else {
      status.value = 'searching';
      currentPage.value = 1;
    }

    const effectiveSortBy = resolveSortValue(collection, sortBy.value, page.props.app?.locale,
      typeof page.props.mainTenantId === 'number' ? page.props.mainTenantId : null);

    try {
      const searchResult = await adminSearch.searchWithFacets(collection, query.value || '*', {
        filterBy: filterString || undefined,
        includeInitialFacets: loadFacetsOnMount && !initialFacetsLoaded.value,
        initialFacetFilterBy: baseFilterBy.value,
        facetFilters: Object.fromEntries(facetConfig.fields.filter((field) => {
          const value = filters.value[field.field];
          return Array.isArray(value) ? value.length > 0 : value !== undefined && value !== null;
        }).map(field => [field.field, [baseFilterBy.value, buildFilterString({ ...filters.value, [field.field]: undefined }, facetConfig)].filter(Boolean).join(' && ')])),
        sortBy: effectiveSortBy,
        facetBy: facetConfig.facetBy,
        queryBy: facetConfig.queryBy,
        perPage,
        page: currentPage.value,
      });

      if (generation !== searchGeneration) return;

      // Update results
      if (isLoadMore) {
        results.value = [...results.value, ...searchResult.hits];
      }
      else {
        results.value = searchResult.hits;
      }

      totalHits.value = searchResult.totalHits;
      totalPages.value = searchResult.totalPages;

      if (!initialFacetsLoaded.value) {
        initialFacets.value = parseFacets(searchResult.initialFacets ?? searchResult.facets, facetConfig, filters.value);
        initialFacetsLoaded.value = true;
      }

      // Update facets from search results
      facets.value = parseFacets(searchResult.facets, facetConfig, filters.value);

      // Sync to URL if enabled
      if (syncToUrl) {
        if (isLoadMore) {
          syncLoadedPagesToUrl();
        }
        else {
          syncStateToUrl();
        }
      }
    }
    catch (err) {
      if (generation !== searchGeneration) return;
      console.error('Search failed:', err);

      // Don't show error for aborted requests
      if (err instanceof Error && err.name === 'AbortError') {
        return;
      }

      error.value = {
        type: 'server',
        message: err instanceof Error ? err.message : 'Unknown error',
        userMessage: 'Paieška nepavyko. Bandyk dar kartą.',
        retryable: true,
      };

      // Reset results on error
      if (!isLoadMore) {
        results.value = [];
        totalHits.value = 0;
        totalPages.value = 0;
      }
    }
    finally {
      if (generation === searchGeneration) status.value = 'idle';
    }
  };

  const performSearch = (isLoadMore = false): Promise<void> => {
    const request = executeSearch(isLoadMore);
    if (loadFacetsOnMount && !initialFacetsLoaded.value) {
      const pending = request.finally(() => {
        if (initialFacetsPromise === pending) initialFacetsPromise = null;
      });
      initialFacetsPromise = pending;
      return pending;
    }
    return request;
  };

  // Debounced search for user typing
  const debouncedSearch = useDebounceFn(() => {
    performSearch(false);
  }, debounceMs);

  /**
   * Set search query and trigger search
   */
  const search = (newQuery: string, immediate = false) => {
    query.value = newQuery;
    filters.value.query = newQuery;

    // Unless the user picked a sort, follow the query: relevance while searching,
    // date order while browsing. Keeps the sort dropdown in sync with results.
    if (!isSortUserSelected.value) {
      sortBy.value = newQuery.trim() !== '' ? RELEVANCE_SORT_VALUE : facetConfig.defaultSortBy;
    }

    if (immediate) {
      performSearch(false);
    }
    else {
      debouncedSearch();
    }
  };

  /**
   * Set a filter value
   */
  const setFilter = (field: string, value: unknown) => {
    filters.value[field] = value as string | string[] | number | number[];
    debouncedSearch();
  };

  /**
   * Toggle a filter value (for multi-select filters)
   */
  const toggleFilter = (field: string, value: string | number) => {
    const currentValue = filters.value[field];

    // Determine if this is a numeric field (like year)
    const isNumeric = field.includes('year') && typeof value === 'number';

    if (isNumeric) {
      filters.value[field] = toggleNumericFilterValue(
        currentValue as number[] | undefined,
        value as number,
      );
    }
    else {
      filters.value[field] = toggleFilterValue(
        currentValue as string[] | undefined,
        String(value),
      );
    }

    debouncedSearch();
  };

  /**
   * Clear all filters (keep query)
   */
  const clearFilters = () => {
    filters.value = clearAllFilters(filters.value, facetConfig);
    debouncedSearch();
  };

  /**
   * Clear everything including query
   */
  const clearAll = () => {
    query.value = '';
    filters.value = { query: '' };
    // Return to date-based browse ordering once the query is gone.
    isSortUserSelected.value = false;
    sortBy.value = facetConfig.defaultSortBy;
    debouncedSearch();
  };

  /**
   * Load more results
   */
  const loadMore = async () => {
    if (status.value === 'idle' && hasMoreResults.value) {
      await performSearch(true);
    }
  };

  /**
   * Change sort order
   */
  const setSortBy = (newSortBy: string) => {
    sortBy.value = newSortBy;
    isSortUserSelected.value = true;
    performSearch(false);
  };

  /**
   * Refresh search (retry)
   */
  const refresh = async () => {
    clearError();
    await performSearch(false);
  };

  /**
   * Sync current state to URL
   */
  const syncStateToUrl = () => {
    if (!syncToUrl) return;

    const params = filtersToUrlParams(filters.value, facetConfig);

    // Add sort if not default
    if (sortBy.value !== facetConfig.defaultSortBy) {
      params.set('sort', sortBy.value);
    }

    // Preserve externally-owned URL keys (e.g. the active tab on the unified search page)
    if (preserveUrlKeys.length > 0) {
      const currentParams = new URLSearchParams(window.location.search);
      for (const key of preserveUrlKeys) {
        const value = currentParams.get(key);
        if (value !== null && !params.has(key)) {
          params.set(key, value);
        }
      }
    }

    // Update URL without page reload
    const newUrl = new URL(window.location.href);
    newUrl.search = params.toString();
    window.history.replaceState({}, '', newUrl.toString());
    filterMemory?.remember();
  };

  /**
   * Record how many pages are loaded so history navigation can restore them. A fresh search
   * rebuilds the URL without `pages`, which is what resets it.
   */
  const syncLoadedPagesToUrl = () => {
    const newUrl = new URL(window.location.href);
    newUrl.searchParams.set('pages', String(currentPage.value));
    window.history.replaceState({}, '', newUrl.toString());
  };

  const withDefaultFilters = (base: AdminSearchFilters): AdminSearchFilters => {
    const defaults = Object.entries(options.defaultFilters ?? {}).filter(([, values]) => values.length > 0);

    return { ...base, ...Object.fromEntries(defaults) };
  };

  /**
   * Load state from URL
   */
  const loadFromUrl = () => {
    if (!syncToUrl) return;

    // Remembered filters first, so a returning visit is not reset to the defaults.
    const isDecided = filterMemory?.restore().decided ?? true;
    const params = new URLSearchParams(window.location.search);

    // Parse filters from URL
    const urlFilters = urlParamsToFilters(params, facetConfig);
    filters.value = isDecided ? urlFilters : withDefaultFilters(urlFilters);
    query.value = urlFilters.query || '';

    if (!isSortUserSelected.value) {
      sortBy.value = query.value.trim() ? RELEVANCE_SORT_VALUE : facetConfig.defaultSortBy;
    }

    // Parse sort from URL
    const urlSort = params.get('sort');
    if (urlSort) {
      sortBy.value = urlSort;
      isSortUserSelected.value = true;
    }

    const urlPages = Number(params.get('pages'));
    pagesToRestore = Number.isInteger(urlPages) && urlPages > 1 ? Math.min(urlPages, MAX_RESTORED_PAGES) : 1;
  };

  let isMounted = false;

  // A new base filter is a new universe: its facets and first page are loaded again.
  watch(baseFilterBy, async () => {
    if (!isMounted) return;

    initialFacetsLoaded.value = false;
    initialFacets.value = [];
    facets.value = [];
    await performSearch(false);
  });

  // Initialize on mount
  onMounted(async () => {
    // Initialize admin search
    await adminSearch.initialize();

    if (!validateCollectionAccess()) return;

    const excludedFields = adminSearch.config.value?.collections[collection]?.excludedFields ?? [];
    facetConfig.fields = facetConfig.fields.filter(field => !excludedFields.includes(field.field));
    facetConfig.facetBy = facetConfig.facetBy.split(',').filter(field => !excludedFields.includes(field)).join(',');
    if (syncToUrl) loadFromUrl();

    isMounted = true;
    if (!searchOnMount && loadFacetsOnMount) await loadInitialFacets();

    if (searchOnMount) {
      await performSearch(false);

      while (currentPage.value < pagesToRestore && hasMoreResults.value) {
        await performSearch(true);
      }
      pagesToRestore = 1;
    }
  });

  // Cleanup on unmount
  onUnmounted(() => {
    isMounted = false;
    searchGeneration++;
    debouncedSearch.cancel();
  });

  return {
    // Config
    facetConfig,
    sortOptions,

    // State
    status,
    query,
    filters,
    sortBy,
    currentPage,
    results,
    totalHits,
    totalPages,
    error,

    // Facets
    facets: mergedFacets,
    initialFacets,
    initialFacetsLoaded,

    // Computed
    isReady,
    isSearching,
    isLoadingFacets,
    isLoadingMore,
    hasResults,
    hasMoreResults,
    activeFilterCount,
    hasActiveFilters,

    // Actions
    search,
    setFilter,
    toggleFilter,
    clearFilters,
    clearAll,
    loadMore,
    setSortBy,
    refresh,
    clearError,
    loadInitialFacets,

    // URL sync
    syncStateToUrl,
    loadFromUrl,

    // Admin search access (for config status, etc.)
    adminSearch,
  };
}

export type AdminCollectionSearchController = ReturnType<typeof useAdminCollectionSearch>;
