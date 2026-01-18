/**
 * Admin Collection Search Composable
 *
 * Main composable for faceted search on a single admin collection.
 * Handles state management, URL sync, facet merging, and search operations.
 */

import { ref, computed, watch, onMounted, onUnmounted, shallowRef, nextTick } from 'vue'
import { useUrlSearchParams } from '@vueuse/core'
import { debounce } from 'lodash-es'

import { useAdminSearch } from '@/Composables/useAdminSearch'
import type {
  AdminCollection,
  AdminFacet,
  AdminSearchFilters,
  AdminSearchStatus,
  AdminSearchError,
  CollectionFacetConfig,
  SortOption,
} from '../Types/AdminSearchTypes'
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
} from '../Services/AdminSearchService'
import { mergeFacets, sortFacetsByConfig } from '../Services/AdminFacetMerger'
import { getCollectionFacetConfig, getCollectionSortOptions } from '../Config/collectionFacetConfig'

export interface UseAdminCollectionSearchOptions {
  collection: AdminCollection
  /** Initial query from URL or default */
  initialQuery?: string
  /** Whether to load initial facets on mount */
  loadFacetsOnMount?: boolean
  /** Whether to perform initial search on mount */
  searchOnMount?: boolean
  /** Whether to sync state to URL */
  syncToUrl?: boolean
  /** Debounce delay for search in ms */
  debounceMs?: number
  /** Results per page */
  perPage?: number
}

export function useAdminCollectionSearch(options: UseAdminCollectionSearchOptions) {
  const {
    collection,
    initialQuery = '',
    loadFacetsOnMount = true,
    searchOnMount = true,
    syncToUrl = true,
    debounceMs = 300,
    perPage = 24,
  } = options

  // Get the facet config for this collection
  const facetConfig = getCollectionFacetConfig(collection)
  if (!facetConfig) {
    throw new Error(`No facet configuration found for collection: ${collection}`)
  }

  // Get the base admin search composable
  const adminSearch = useAdminSearch()

  // URL params for state persistence
  const urlParams = syncToUrl ? useUrlSearchParams('history') : ref({})

  // State
  const status = ref<AdminSearchStatus>('idle')
  const query = ref(initialQuery)
  const filters = ref<AdminSearchFilters>({ query: initialQuery })
  const sortBy = ref(facetConfig.defaultSortBy)
  const currentPage = ref(1)

  // Results state (using shallowRef for performance with large arrays)
  const results = shallowRef<unknown[]>([])
  const totalHits = ref(0)
  const totalPages = ref(0)

  // Facets state
  const facets = shallowRef<AdminFacet[]>([])
  const initialFacets = shallowRef<AdminFacet[]>([])
  const initialFacetsLoaded = ref(false)

  // Error state
  const error = ref<AdminSearchError | null>(null)

  // Abort controller for cleanup
  let searchAbortController: AbortController | null = null

  // Sort options for this collection
  const sortOptions = computed<SortOption[]>(() => getCollectionSortOptions(collection))

  // Computed properties
  const isSearching = computed(() => status.value === 'searching')
  const isLoadingFacets = computed(() => status.value === 'loading-facets')
  const isLoadingMore = computed(() => status.value === 'loading-more')
  const hasResults = computed(() => results.value.length > 0)
  const hasMoreResults = computed(() => currentPage.value < totalPages.value)
  const activeFilterCount = computed(() => countActiveFilters(filters.value, facetConfig))
  const hasActiveFilters = computed(() => checkActiveFilters(filters.value, facetConfig))

  // Merged facets (initial + current with selection state)
  const mergedFacets = computed(() => {
    const merged = mergeFacets(initialFacets.value, facets.value, filters.value, facetConfig)
    return sortFacetsByConfig(merged, facetConfig)
  })

  // Search ready state
  const isReady = computed(() => adminSearch.isReady.value && initialFacetsLoaded.value)

  /**
   * Clear error state
   */
  const clearError = () => {
    error.value = null
  }

  /**
   * Load initial facets (all available filter options)
   */
  const loadInitialFacets = async () => {
    if (initialFacetsLoaded.value) return

    status.value = 'loading-facets'

    try {
      const rawFacets = await adminSearch.loadInitialFacets(collection, facetConfig.facetBy)

      initialFacets.value = parseFacets(rawFacets, facetConfig, filters.value)
      initialFacetsLoaded.value = true

      // If no current facets, use initial as default
      if (facets.value.length === 0) {
        facets.value = [...initialFacets.value]
      }
    } catch (err) {
      console.error('Failed to load initial facets:', err)
      error.value = {
        type: 'server',
        message: err instanceof Error ? err.message : 'Unknown error',
        userMessage: 'Nepavyko įkelti filtrų. Bandykite dar kartą.',
        retryable: true,
      }
    } finally {
      status.value = 'idle'
    }
  }

  /**
   * Perform search with current filters
   */
  const performSearch = async (isLoadMore = false) => {
    // Clear previous error
    if (!isLoadMore) {
      clearError()
    }

    // Build filter string from current filters
    const filterString = buildFilterString(filters.value, facetConfig)

    // Set loading state
    if (isLoadMore) {
      status.value = 'loading-more'
      currentPage.value += 1
    } else {
      status.value = 'searching'
      currentPage.value = 1
    }

    try {
      const searchResult = await adminSearch.searchWithFacets(collection, query.value || '*', {
        filterBy: filterString || undefined,
        sortBy: sortBy.value,
        facetBy: facetConfig.facetBy,
        queryBy: facetConfig.queryBy,
        perPage,
        page: currentPage.value,
      })

      // Update results
      if (isLoadMore) {
        results.value = [...results.value, ...searchResult.hits]
      } else {
        results.value = searchResult.hits
      }

      totalHits.value = searchResult.totalHits
      totalPages.value = searchResult.totalPages

      // Update facets from search results
      facets.value = parseFacets(searchResult.facets, facetConfig, filters.value)

      // Sync to URL if enabled
      if (syncToUrl && !isLoadMore) {
        syncStateToUrl()
      }
    } catch (err) {
      console.error('Search failed:', err)

      // Don't show error for aborted requests
      if (err instanceof Error && err.name === 'AbortError') {
        return
      }

      error.value = {
        type: 'server',
        message: err instanceof Error ? err.message : 'Unknown error',
        userMessage: 'Paieška nepavyko. Bandykite dar kartą.',
        retryable: true,
      }

      // Reset results on error
      if (!isLoadMore) {
        results.value = []
        totalHits.value = 0
        totalPages.value = 0
      }
    } finally {
      status.value = 'idle'
    }
  }

  // Debounced search for user typing
  const debouncedSearch = debounce(() => {
    if (status.value === 'idle' || status.value === 'error') {
      performSearch(false)
    }
  }, debounceMs)

  /**
   * Set search query and trigger search
   */
  const search = (newQuery: string, immediate = false) => {
    query.value = newQuery
    filters.value.query = newQuery

    if (immediate) {
      performSearch(false)
    } else {
      debouncedSearch()
    }
  }

  /**
   * Set a filter value
   */
  const setFilter = (field: string, value: unknown) => {
    filters.value[field] = value as string | string[] | number | number[]
    debouncedSearch()
  }

  /**
   * Toggle a filter value (for multi-select filters)
   */
  const toggleFilter = (field: string, value: string | number) => {
    const currentValue = filters.value[field]

    // Determine if this is a numeric field (like year)
    const isNumeric = field.includes('year') && typeof value === 'number'

    if (isNumeric) {
      filters.value[field] = toggleNumericFilterValue(
        currentValue as number[] | undefined,
        value as number
      )
    } else {
      filters.value[field] = toggleFilterValue(
        currentValue as string[] | undefined,
        String(value)
      )
    }

    debouncedSearch()
  }

  /**
   * Clear all filters (keep query)
   */
  const clearFilters = () => {
    filters.value = clearAllFilters(filters.value, facetConfig)
    debouncedSearch()
  }

  /**
   * Clear everything including query
   */
  const clearAll = () => {
    query.value = ''
    filters.value = { query: '' }
    debouncedSearch()
  }

  /**
   * Load more results
   */
  const loadMore = async () => {
    if (status.value !== 'loading-more' && hasMoreResults.value) {
      await performSearch(true)
    }
  }

  /**
   * Change sort order
   */
  const setSortBy = (newSortBy: string) => {
    sortBy.value = newSortBy
    performSearch(false)
  }

  /**
   * Refresh search (retry)
   */
  const refresh = async () => {
    clearError()
    await performSearch(false)
  }

  /**
   * Sync current state to URL
   */
  const syncStateToUrl = () => {
    if (!syncToUrl) return

    const params = filtersToUrlParams(filters.value, facetConfig)

    // Add sort if not default
    if (sortBy.value !== facetConfig.defaultSortBy) {
      params.set('sort', sortBy.value)
    }

    // Update URL without page reload
    const newUrl = new URL(window.location.href)
    newUrl.search = params.toString()
    window.history.replaceState({}, '', newUrl.toString())
  }

  /**
   * Load state from URL
   */
  const loadFromUrl = () => {
    if (!syncToUrl) return

    const params = new URLSearchParams(window.location.search)

    // Parse filters from URL
    const urlFilters = urlParamsToFilters(params, facetConfig)
    filters.value = urlFilters
    query.value = urlFilters.query || ''

    // Parse sort from URL
    const urlSort = params.get('sort')
    if (urlSort) {
      sortBy.value = urlSort
    }
  }

  // Watch for config changes to re-search
  watch(
    () => adminSearch.config.value,
    (newConfig) => {
      if (newConfig && !initialFacetsLoaded.value) {
        loadInitialFacets()
      }
    }
  )

  // Initialize on mount
  onMounted(async () => {
    // Initialize admin search
    await adminSearch.initialize()

    // Load state from URL if enabled
    if (syncToUrl) {
      loadFromUrl()
    }

    // Load initial facets
    if (loadFacetsOnMount) {
      await loadInitialFacets()
    }

    // Perform initial search
    if (searchOnMount) {
      await nextTick()
      await performSearch(false)
    }
  })

  // Cleanup on unmount
  onUnmounted(() => {
    if (searchAbortController) {
      searchAbortController.abort()
    }
    debouncedSearch.cancel()
  })

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
  }
}

export type AdminCollectionSearchController = ReturnType<typeof useAdminCollectionSearch>
