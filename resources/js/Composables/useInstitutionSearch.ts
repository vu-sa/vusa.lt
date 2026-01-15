import { ref, computed, watch, onUnmounted, nextTick, shallowRef } from 'vue'
import { useLocalStorage, useOnline } from '@vueuse/core'
import { debounce } from 'lodash-es'
import { usePage } from '@inertiajs/vue3'
import { createTypesenseClients } from './useSearchClient'

// Import types and services
import type {
  InstitutionSearchFilters,
  InstitutionFacet,
  InstitutionSearchPreferences,
  InstitutionSearchController,
  ProcessedInstitutionResult
} from '../Types/InstitutionSearchTypes'
import type { SearchError, SearchStatus, SearchState } from '../Types/DocumentSearchTypes'
import { InstitutionSearchService } from '../Services/InstitutionSearchService'
import { FacetMerger } from '../Services/FacetMerger'
import {
  RecentSearchManager,
  FilterUtils,
  ErrorUtils,
  QueryUtils
} from '../Utils/SearchUtils'

// Re-export types for backward compatibility
export type {
  InstitutionSearchFilters,
  InstitutionFacet,
  InstitutionSearchPreferences
}

export const useInstitutionSearch = (): InstitutionSearchController => {
  // Persistent preferences
  const preferences = useLocalStorage<InstitutionSearchPreferences>('institution-search-preferences', {
    viewMode: 'grid', // Default to grid view for institutions
    recentSearches: []
  })

  // Simplified state management with status - using shallowRef for performance with large arrays
  const status = ref<SearchStatus>('idle')
  const searchQuery = ref('') // This is what the user sees in the input
  const internalQuery = ref('') // This is what we actually search for
  const results = shallowRef<any[]>([]) // Performance: use shallowRef for large arrays
  const totalHits = ref(0)
  const facets = shallowRef<InstitutionFacet[]>([]) // Performance: use shallowRef for facets
  const initialFacets = shallowRef<InstitutionFacet[]>([]) // Performance: use shallowRef for initial facets

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
  const filters = ref<InstitutionSearchFilters>({
    query: '',
    tenants: [],
    types: [],
    hasContacts: null
  })

  // Search clients
  const searchClient = ref<any>(null) // InstantSearch adapter (for compatibility)
  const typesenseClient = ref<any>(null) // Direct Typesense client

  // Services
  const searchService = ref<InstitutionSearchService | null>(null)

  // Get current locale
  const getCurrentLocale = (): string => {
    const page = usePage()
    return (page.props.app as any)?.locale || 'lt'
  }

  // Computed properties for intelligent facet merging
  const facetMergeKey = computed(() => {
    return JSON.stringify({
      initialCount: initialFacets.value.length,
      currentCount: facets.value.length,
      filters: {
        tenants: filters.value.tenants.slice().sort(),
        types: filters.value.types.slice().sort(),
        hasContacts: filters.value.hasContacts
      }
    })
  })

  const mergedFacets = computed(() => {
    facetMergeKey.value // Touch the key for reactivity

    return FacetMerger.mergeFacetsWithSelectionMap(
      initialFacets.value,
      facets.value,
      {
        tenant_shortname: filters.value.tenants,
        type_slugs: filters.value.types
      }
    )
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
      console.warn('Typesense not configured - institution search unavailable')
      return null
    }

    try {
      // Get collection name from config (with staging prefix if applicable)
      const collectionName = typesenseConfig.collections?.public_institutions || 'public_institutions'
      
      // Build collection-specific search parameters with dynamic collection name
      const collectionSpecificSearchParameters: Record<string, any> = {}
      collectionSpecificSearchParameters[collectionName] = {
        query_by: 'name_lt,name_en,short_name_lt,short_name_en,alias',
        facet_by: [
          'tenant_shortname',
          'type_slugs',
          'has_contacts'
        ].join(','),
        per_page: 24,
      }

      const clients = createTypesenseClients(typesenseConfig, {
        additionalSearchParameters: {
          query_by: 'name_lt,name_en,short_name_lt,short_name_en,alias',
          num_typos: 2,
          typo_tokens_threshold: 1,
          drop_tokens_threshold: 1,
          max_hits: 1000,
          per_page: 24,
          facet_by: [
            'tenant_shortname',
            'type_slugs',
            'has_contacts'
          ].join(','),
          max_facet_values: 50,
          sort_by: 'duties_count:desc,updated_at:desc'
        },
        collectionSpecificSearchParameters
      })

      searchClient.value = clients.searchClient
      typesenseClient.value = clients.typesenseClient

      // Initialize search service with collection name from config
      searchService.value = new InstitutionSearchService(typesenseClient.value, collectionName)

      // Load initial facets after client initialization
      if (typesenseClient.value) {
        await nextTick()
        await loadInitialFacets()
      }

      return clients.searchClient
    } catch (error) {
      console.error('Failed to initialize Typesense clients:', error)
      return null
    }
  }

  // Search function using service
  const performSearch = async (isLoadMore = false, attempt = 1) => {
    if (!isLoadMore && attempt === 1) {
      clearError()
    }

    if (!searchService.value) {
      handleSearchError(new Error('Search service not initialized'), 'service initialization')
      return
    }

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

    // Set loading states
    if (isLoadMore) {
      status.value = 'loading-more'
    } else {
      status.value = 'searching'
    }

    try {
      const result = await searchService.value.performSearch(
        filters.value,
        perPage.value,
        isLoadMore,
        currentPage.value,
        getCurrentLocale()
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

      // Add to recent searches
      if (QueryUtils.isValidQuery(searchQuery.value)) {
        preferences.value.recentSearches = RecentSearchManager.addToRecentSearches(
          preferences.value.recentSearches,
          searchQuery.value
        )
      }

      retryCount.value = 0

    } catch (error) {
      if (!ErrorUtils.isAbortError(error)) {
        handleSearchError(error, `search attempt ${attempt}`)

        if (searchError.value?.retryable && attempt < maxRetries) {
          console.log(`Retrying search, attempt ${attempt + 1}/${maxRetries}`)
          retryCount.value = attempt

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

  // Debounced search function
  const debouncedSearch = debounce(() => {
    if (status.value === 'idle' || status.value === 'error') {
      performSearch(false)
    }
  }, 200)

  // Cache for initial facets
  const initialFacetsLoaded = ref(false)

  // Load initial facets
  const loadInitialFacets = async () => {
    if (!searchService.value || initialFacetsLoaded.value) {
      return
    }

    status.value = 'loading-facets'

    try {
      const facetResults = await searchService.value.loadInitialFacets()

      initialFacets.value = facetResults
      initialFacetsLoaded.value = true

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
    if (query === '*') {
      internalQuery.value = '*'
      filters.value.query = '*'
      searchQuery.value = ''
    } else {
      searchQuery.value = query
      internalQuery.value = query
      filters.value.query = query
    }

    if (immediate) {
      performSearch(false)
    } else {
      debouncedSearch()
    }
  }

  const setFilter = <K extends keyof InstitutionSearchFilters>(
    key: K,
    value: InstitutionSearchFilters[K]
  ) => {
    filters.value[key] = value
    debouncedSearch()
  }

  const toggleTenant = (tenantShortname: string) => {
    filters.value.tenants = FilterUtils.toggleArrayValue(filters.value.tenants, tenantShortname)
    debouncedSearch()
  }

  const toggleType = (typeSlug: string) => {
    filters.value.types = FilterUtils.toggleArrayValue(filters.value.types, typeSlug)
    debouncedSearch()
  }

  const setHasContacts = (value: boolean | null) => {
    filters.value.hasContacts = value
    debouncedSearch()
  }

  const setViewMode = (mode: 'grid' | 'list') => {
    preferences.value.viewMode = mode
  }

  const clearFilters = () => {
    filters.value = {
      query: filters.value.query, // Keep the search query
      tenants: [],
      types: [],
      hasContacts: null
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

  // Computed properties
  const hasActiveFilters = computed(() => {
    return filters.value.tenants.length > 0 ||
           filters.value.types.length > 0 ||
           filters.value.hasContacts !== null
  })

  const hasResults = computed(() => results.value.length > 0)
  const hasMoreResults = computed(() => currentPage.value < totalPages.value)

  // Use any type for searchState since InstitutionSearch has different view modes
  const searchState = computed(() => ({
    isSearching: status.value === 'searching',
    hasResults: hasResults.value,
    totalHits: totalHits.value,
    query: searchQuery.value,
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
    facets: computed(() => mergedFacets.value),
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
    toggleType,
    setHasContacts,
    setViewMode,
    clearFilters,
    clearRecentSearches,
    removeRecentSearch,
    loadMore,
    retrySearch,
    clearError,
    loadInitialFacets,

    // Internal
    searchClient: computed(() => searchClient.value),
    initializeSearchClient
  }
}
