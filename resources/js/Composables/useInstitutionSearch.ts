import { ref, computed, watch, onUnmounted, nextTick, shallowRef } from 'vue'
import { useLocalStorage } from '@vueuse/core'
import { debounce } from 'lodash-es'
import TypesenseInstantSearchAdapter from 'typesense-instantsearch-adapter'
import { usePage } from '@inertiajs/vue3'

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
import {
  RecentSearchManager,
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
  const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)
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
    
    // Simple facet merging for institutions
    if (initialFacets.value.length === 0) {
      return facets.value
    }
    
    return initialFacets.value.map(initialFacet => {
      const currentFacet = facets.value.find(f => f.field === initialFacet.field)
      
      const currentValueMap = new Map<string, number>()
      if (currentFacet) {
        currentFacet.values.forEach(value => {
          currentValueMap.set(value.value, value.count)
        })
      }
      
      // Get selected values for this facet field
      const selectedValues = initialFacet.field === 'tenant_shortname' 
        ? filters.value.tenants 
        : initialFacet.field === 'type_slugs' 
          ? filters.value.types 
          : []
      
      const mergedValues = initialFacet.values.map(initialValue => ({
        ...initialValue,
        count: currentValueMap.get(initialValue.value) || 0,
        isSelected: selectedValues.includes(initialValue.value)
      }))
      
      // Add any new values from current search
      if (currentFacet) {
        currentFacet.values.forEach(currentValue => {
          if (!initialFacet.values.some(iv => iv.value === currentValue.value)) {
            mergedValues.push({
              ...currentValue,
              isSelected: selectedValues.includes(currentValue.value)
            })
          }
        })
      }
      
      // Sort: selected first, then by count, then alphabetically
      mergedValues.sort((a, b) => {
        if (a.isSelected && !b.isSelected) return -1
        if (!a.isSelected && b.isSelected) return 1
        if (a.count !== b.count) return b.count - a.count
        return a.value.localeCompare(b.value)
      })
      
      return { ...initialFacet, values: mergedValues }
    })
  })

  // Network status monitoring
  const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine
  }

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
      // Create InstantSearch adapter (for compatibility)
      const adapter = new TypesenseInstantSearchAdapter({
        server: {
          apiKey: typesenseConfig.apiKey,
          nodes: typesenseConfig.nodes,
          connectionTimeoutSeconds: 10,
        },
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
        collectionSpecificSearchParameters: {
          public_institutions: {
            query_by: 'name_lt,name_en,short_name_lt,short_name_en,alias',
            facet_by: [
              'tenant_shortname',
              'type_slugs',
              'has_contacts'
            ].join(','),
            per_page: 24,
          }
        }
      })

      searchClient.value = adapter.searchClient

      // Create direct Typesense client for service usage
      typesenseClient.value = {
        apiKey: typesenseConfig.apiKey,
        nodes: typesenseConfig.nodes,
        search: async (collection: string, searchParams: any, abortSignal?: AbortSignal) => {
          const node = typesenseConfig.nodes[0]
          const baseUrl = `${node.protocol}://${node.host}:${node.port}`
          const url = new URL(`${baseUrl}/collections/${collection}/documents/search`)

          Object.entries(searchParams).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
              url.searchParams.append(key, String(value))
            }
          })

          const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
              'X-TYPESENSE-API-KEY': typesenseConfig.apiKey,
              'Content-Type': 'application/json',
            },
            signal: abortSignal
          })

          if (!response.ok) {
            const errorText = await response.text()
            throw new Error(`Typesense API error: ${response.status} - ${errorText}`)
          }

          return await response.json()
        }
      }

      // Initialize search service
      searchService.value = new InstitutionSearchService(typesenseClient.value)

      // Load initial facets after client initialization
      if (typesenseClient.value) {
        await nextTick()
        await loadInitialFacets()
      }

      return adapter.searchClient
    } catch (error) {
      console.error('Failed to initialize Typesense clients:', error)
      return null
    }
  }

  // Check if search should be performed for institutions
  const shouldPerformSearch = (): boolean => {
    const hasActiveFilters = filters.value.tenants.length > 0 ||
                            filters.value.types.length > 0 ||
                            filters.value.hasContacts !== null
    const query = filters.value.query
    
    // For institutions, always allow search (even empty query means "show all")
    return query === '*' || query.trim().length >= 0 || hasActiveFilters
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
      if (searchQuery.value && searchQuery.value !== '*' && searchQuery.value.trim().length > 0) {
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
    const current = [...filters.value.tenants]
    const index = current.indexOf(tenantShortname)

    if (index >= 0) {
      current.splice(index, 1)
    } else {
      current.push(tenantShortname)
    }

    filters.value.tenants = current
    debouncedSearch()
  }

  const toggleType = (typeSlug: string) => {
    const current = [...filters.value.types]
    const index = current.indexOf(typeSlug)

    if (index >= 0) {
      current.splice(index, 1)
    } else {
      current.push(typeSlug)
    }

    filters.value.types = current
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

  // Network monitoring setup
  if (typeof window !== 'undefined') {
    window.addEventListener('online', updateOnlineStatus)
    window.addEventListener('offline', updateOnlineStatus)
  }

  // Cleanup on unmount
  onUnmounted(() => {
    if (searchService.value) {
      searchService.value.cancelCurrentSearch()
    }
    debouncedSearch.cancel()

    if (typeof window !== 'undefined') {
      window.removeEventListener('online', updateOnlineStatus)
      window.removeEventListener('offline', updateOnlineStatus)
    }
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
