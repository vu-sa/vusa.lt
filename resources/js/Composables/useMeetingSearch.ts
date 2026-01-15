import { ref, computed, watch, onUnmounted, nextTick, shallowRef } from 'vue'
import { useLocalStorage, useOnline } from '@vueuse/core'
import { debounce } from 'lodash-es'
import { usePage } from '@inertiajs/vue3'
import { createTypesenseClients } from './useSearchClient'

// Import types and services
import type {
  MeetingSearchFilters,
  MeetingFacet,
  SearchError,
  SearchStatus,
  SearchState,
  MeetingSearchPreferences,
  MeetingSearchController
} from '../Types/MeetingSearchTypes'
import { MeetingSearchService } from '../Services/MeetingSearchService'
import { FacetMerger } from '../Services/FacetMerger'
import {
  RecentSearchManager,
  FilterUtils,
  ErrorUtils,
  QueryUtils
} from '../Utils/SearchUtils'

// Re-export types for backward compatibility
export type {
  MeetingSearchFilters,
  MeetingFacet,
  SearchError,
  SearchStatus,
  SearchState,
  MeetingSearchPreferences
}

export const useMeetingSearch = (): MeetingSearchController => {
  // Persistent preferences
  const preferences = useLocalStorage<MeetingSearchPreferences>('meeting-search-preferences', {
    viewMode: 'list', // Default to list view
    recentSearches: []
  })

  // Simplified state management with status - using shallowRef for performance with large arrays
  const status = ref<SearchStatus>('idle')
  const searchQuery = ref('') // This is what the user sees in the input
  const internalQuery = ref('') // This is what we actually search for
  const results = shallowRef<any[]>([]) // Performance: use shallowRef for large arrays
  const totalHits = ref(0)
  const facets = shallowRef<MeetingFacet[]>([]) // Performance: use shallowRef for facets
  const initialFacets = shallowRef<MeetingFacet[]>([]) // Performance: use shallowRef for initial facets

  // Error handling state with user-friendly messages
  const searchError = ref<SearchError | null>(null)
  const isOnline = useOnline()
  const retryCount = ref(0)
  const maxRetries = 3

  // Pagination state
  const currentPage = ref(0)
  const totalPages = ref(0)
  const perPage = ref(24)

  // Filter state - consolidated into single source of truth
  const filters = ref<MeetingSearchFilters>({
    query: '',
    tenants: [],
    institutionTypes: [],
    years: [],
    successRateRanges: [],
    dateRange: {}
  })

  // Search clients
  const searchClient = ref<any>(null) // InstantSearch adapter (for compatibility)
  const typesenseClient = ref<any>(null) // Direct Typesense client

  // Services
  const searchService = ref<MeetingSearchService | null>(null)

  // Computed properties for intelligent facet merging using service with memoization key
  const facetMergeKey = computed(() => {
    // Create a stable key for memoization based on facet data and filters
    return JSON.stringify({
      initialCount: initialFacets.value.length,
      currentCount: facets.value.length,
      filters: {
        tenants: filters.value.tenants.slice().sort(),
        years: filters.value.years.slice().sort(),
        successRateRanges: filters.value.successRateRanges.slice().sort()
      }
    })
  })

  const mergedFacets = computed(() => {
    // The key dependency ensures this only recalculates when necessary
    facetMergeKey.value // Touch the key for reactivity
    return FacetMerger.mergeFacets(initialFacets.value, facets.value, filters.value)
  })

  // Simplified error handling using utility
  const clearError = () => {
    searchError.value = null
    retryCount.value = 0
    status.value = 'idle'
  }

  const handleSearchError = (error: unknown, context: string) => {
    console.error(`Search error in ${context}:`, error)

    const errorType = ErrorUtils.getErrorType(error)
    const userMessage = ErrorUtils.getUserFriendlyMessage(error)
    const message = error instanceof Error ? error.message : 'Unknown error'
    const retryable = !ErrorUtils.isAbortError(error)

    searchError.value = ErrorUtils.createSearchError(
      errorType,
      message,
      userMessage,
      errorType.toUpperCase(),
      retryable
    )

    if (ErrorUtils.isAbortError(error)) {
      return // Don't set error status for aborted requests
    }

    status.value = 'error'
  }

  // Initialize Typesense search client
  const initializeSearchClient = async () => {
    const page = usePage()
    const typesenseConfig = page.props.typesenseConfig as any

    if (!typesenseConfig?.apiKey) {
      console.warn('Typesense not configured - meeting search unavailable')
      return null
    }

    try {
      const locale = page.props.app.locale || 'lt'
      const institutionNameField = `institution_name_${locale}`

      // Get collection name from config (with staging prefix if applicable)
      const collectionName = typesenseConfig.collections?.public_meetings || 'public_meetings'
      
      // Build collection-specific search parameters with dynamic collection name
      const collectionSpecificSearchParameters: Record<string, any> = {}
      collectionSpecificSearchParameters[collectionName] = {
        query_by: `title,description,${institutionNameField}`,
        facet_by: [
          'year',
          'month',
          'tenant_shortname'
        ].join(','),
        per_page: 24,
      }

      const clients = createTypesenseClients(typesenseConfig, {
        additionalSearchParameters: {
          query_by: `title,description,${institutionNameField}`,
          num_typos: 2,
          typo_tokens_threshold: 1,
          drop_tokens_threshold: 1,
          max_hits: 1000,
          per_page: 24,
          facet_by: [
            'year',
            'month',
            'tenant_shortname'
          ].join(','),
          max_facet_values: 50,
          sort_by: 'start_time:desc'
        },
        collectionSpecificSearchParameters
      })

      searchClient.value = clients.searchClient
      typesenseClient.value = clients.typesenseClient

      // Initialize search service with collection name from config
      searchService.value = new MeetingSearchService(typesenseClient.value, collectionName)

      // Load initial facets after client initialization
      if (typesenseClient.value) {
        // Use nextTick to ensure the client is fully set up
        await nextTick()
        await loadInitialFacets()
      }

      return clients.searchClient
    } catch (error) {
      console.error('Failed to initialize Typesense clients:', error)
      return null
    }
  }

  // Search function using service - much cleaner!
  const performSearch = async (isLoadMore = false, attempt = 1) => {
    // Clear previous errors on new search attempts
    if (!isLoadMore && attempt === 1) {
      clearError()
    }

    if (!searchService.value) {
      handleSearchError(new Error('Search service not initialized'), 'service initialization')
      return
    }

    // Check network connectivity
    if (!isOnline.value) {
      searchError.value = ErrorUtils.createSearchError(
        'network',
        'No internet connection',
        'Check your internet connection and try again',
        'OFFLINE',
        true
      )
      return
    }

    // Validate search using utility
    const hasActiveFilters = FilterUtils.hasActiveFilters(filters.value)
    const shouldSearch = QueryUtils.shouldSearch(filters.value.query, hasActiveFilters)

    if (!shouldSearch) {
      results.value = []
      totalHits.value = 0
      facets.value = []
      currentPage.value = 0
      totalPages.value = 0
      status.value = 'idle'
      return
    }

    // Set loading states
    if (isLoadMore) {
      status.value = 'loading-more'
    } else {
      status.value = 'searching'
    }

    try {
      // Use service to perform search - all the complex logic is now encapsulated
      const result = await searchService.value.performSearch(
        filters.value,
        perPage.value,
        isLoadMore,
        currentPage.value
      )

      // Update state with results
      if (isLoadMore) {
        results.value = [...results.value, ...result.hits]
      } else {
        results.value = result.hits
      }

      totalHits.value = result.totalHits
      totalPages.value = result.totalPages
      currentPage.value = result.currentPage
      facets.value = result.facets

      // Add to recent searches using utility - use the user's actual query, not internal query
      if (QueryUtils.isValidQuery(searchQuery.value)) {
        preferences.value.recentSearches = RecentSearchManager.addToRecentSearches(
          preferences.value.recentSearches,
          searchQuery.value
        )
      }

      // Reset retry count on successful search
      retryCount.value = 0

    } catch (error) {
      if (!ErrorUtils.isAbortError(error)) {
        handleSearchError(error, `search attempt ${attempt}`)

        // Retry logic for retryable errors
        if (searchError.value?.retryable && attempt < maxRetries) {
          console.log(`Retrying search, attempt ${attempt + 1}/${maxRetries}`)
          retryCount.value = attempt

          // Exponential backoff: 1s, 2s, 4s
          const delay = Math.pow(2, attempt - 1) * 1000
          setTimeout(() => {
            performSearch(isLoadMore, attempt + 1)
          }, delay)
          return
        }
      }
    } finally {
      status.value = 'idle'
    }
  }

  // Debounced search function (for new searches) with optimization
  const debouncedSearch = debounce(() => {
    // Only perform search if not already searching or loading
    if (status.value === 'idle' || status.value === 'error') {
      performSearch(false)
    }
  }, 300)

  // Cache for initial facets to avoid unnecessary API calls
  const initialFacetsLoaded = ref(false)

  // Load initial facets using service with caching
  const loadInitialFacets = async () => {
    if (!searchService.value || initialFacetsLoaded.value) {
      return // Already loaded, skip
    }

    status.value = 'loading-facets'

    try {
      const facetResults = await searchService.value.loadInitialFacets()

      initialFacets.value = facetResults
      initialFacetsLoaded.value = true // Mark as loaded

      // If no current facets, use initial facets as default
      if (facets.value.length === 0) {
        facets.value = [...initialFacets.value]
      }

    } catch (error) {
      console.error('Failed to load initial facets:', error)
      initialFacets.value = []
    } finally {
      status.value = 'idle'
    }
  }

  // Public API methods
  const search = (query: string, immediate = false) => {
    // If it's a wildcard search (for loading all meetings), don't show it in the input
    if (query === '*') {
      internalQuery.value = '*'
      filters.value.query = '*'
      // Keep searchQuery empty so input shows placeholder
      searchQuery.value = ''
    } else {
      // Normal search - show in input and use for search
      searchQuery.value = query
      internalQuery.value = query
      filters.value.query = query
    }

    // Use immediate search for initial load, debounced for user typing
    if (immediate) {
      performSearch(false)
    } else {
      debouncedSearch()
    }
  }

  const setFilter = <K extends keyof MeetingSearchFilters>(
    key: K,
    value: MeetingSearchFilters[K]
  ) => {
    filters.value[key] = value
    debouncedSearch()
  }

  const toggleTenant = (tenantShortname: string) => {
    filters.value.tenants = FilterUtils.toggleArrayValue(filters.value.tenants, tenantShortname)
    debouncedSearch()
  }

  const toggleInstitutionType = (type: string) => {
    filters.value.institutionTypes = FilterUtils.toggleArrayValue(filters.value.institutionTypes, type)
    debouncedSearch()
  }

  const toggleYear = (year: number) => {
    filters.value.years = FilterUtils.toggleArrayValue(filters.value.years, year)
    debouncedSearch()
  }

  const toggleSuccessRate = (range: string) => {
    filters.value.successRateRanges = FilterUtils.toggleArrayValue(filters.value.successRateRanges, range)
    debouncedSearch()
  }

  const setDateRange = (dateRange: any) => {
    filters.value.dateRange = {
      from: dateRange.from,
      to: dateRange.to,
      preset: dateRange.preset
    }
    debouncedSearch()
  }

  const setViewMode = (mode: 'list' | 'compact') => {
    preferences.value.viewMode = mode
  }

  const clearFilters = () => {
    filters.value = {
      query: filters.value.query,
      tenants: [],
      institutionTypes: [],
      years: [],
      successRateRanges: [],
      dateRange: {}
    }
    debouncedSearch()
  }

  const clearRecentSearches = () => {
    preferences.value.recentSearches = RecentSearchManager.clearRecentSearches()
  }

  const removeRecentSearch = (searchToRemove: string) => {
    preferences.value.recentSearches = RecentSearchManager.removeRecentSearch(
      preferences.value.recentSearches,
      searchToRemove
    )
  }

  const loadMore = () => {
    if (status.value !== 'loading-more' && currentPage.value < totalPages.value) {
      performSearch(true)
    }
  }

  // Computed properties using utilities
  const hasActiveFilters = computed(() => FilterUtils.hasActiveFilters(filters.value))

  const hasResults = computed(() => results.value.length > 0)
  const hasMoreResults = computed(() => currentPage.value < totalPages.value)

  const searchState = computed<SearchState>(() => ({
    isSearching: status.value === 'searching',
    hasResults: hasResults.value,
    totalHits: totalHits.value,
    query: searchQuery.value, // Use display query, not internal query
    filters: filters.value,
    facets: facets.value,
    results: results.value,
    viewMode: preferences.value.viewMode,
    error: searchError.value,
    isOnline: isOnline.value,
    status: status.value
  }))

  // Watch for query changes from external sources
  watch(() => filters.value.query, (newQuery) => {
    // Only update display query if it's not a wildcard
    if (newQuery !== '*') {
      searchQuery.value = newQuery
    }
    internalQuery.value = newQuery
  })

  // Retry functionality
  const retrySearch = () => {
    if (searchError.value?.retryable) {
      clearError()
      performSearch(false, 1)
    }
  }

  // Cleanup on unmount
  onUnmounted(() => {
    // Cancel any ongoing searches through service
    if (searchService.value) {
      searchService.value.cancelCurrentSearch()
    }
    debouncedSearch.cancel()

  })

  return {
    // State
    searchState,
    isSearching: computed(() => status.value === 'searching'),
    isLoadingFacets: computed(() => status.value === 'loading-facets'),
    isLoadingMore: computed(() => status.value === 'loading-more'),
    hasResults,
    hasActiveFilters,
    hasMoreResults,
    totalHits: computed(() => totalHits.value),
    results: computed(() => results.value),
    facets: computed(() => mergedFacets.value), // Use merged facets instead of raw facets
    filters: computed(() => filters.value),
    viewMode: computed(() => preferences.value.viewMode),
    recentSearches: computed(() => preferences.value.recentSearches),

    // Error handling
    searchError: computed(() => searchError.value),
    isOnline: computed(() => isOnline.value),
    retryCount: computed(() => retryCount.value),
    maxRetries,

    // Actions
    search,
    setFilter,
    toggleTenant,
    toggleInstitutionType,
    toggleYear,
    toggleSuccessRate,
    setDateRange,
    setViewMode,
    clearFilters,
    clearRecentSearches,
    removeRecentSearch,
    loadMore,
    retrySearch,
    clearError,
    loadInitialFacets, // Expose for manual loading

    // Internal
    searchClient: computed(() => searchClient.value),
    initializeSearchClient
  }
}
