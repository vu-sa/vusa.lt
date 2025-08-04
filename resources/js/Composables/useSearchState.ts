import { ref, computed, type Ref } from 'vue'
import { useLocalStorage } from '@vueuse/core'

// Core interfaces
export interface DocumentSearchFilters {
  query: string
  tenants: string[]
  contentTypes: string[]
  languages: string[]
  dateRange: {
    from?: Date
    to?: Date
    preset?: 'recent' | '3months' | '6months' | '1year' | 'year-range' | 'custom'
  }
}

export interface DocumentFacet {
  field: string
  label: string
  values: Array<{
    value: string
    label: string
    count: number
    highlighted?: string
    isSelected?: boolean
  }>
}

export interface DocumentSearchPreferences {
  viewMode: 'grid' | 'list'
  recentSearches: string[]
  favoriteFilters: Partial<DocumentSearchFilters>[]
}

export type SearchStatus = 'idle' | 'searching' | 'loading-more' | 'loading-facets' | 'error'

export const useSearchState = () => {
  // Persistent preferences
  const preferences = useLocalStorage<DocumentSearchPreferences>('document-search-preferences', {
    viewMode: 'list',
    recentSearches: [],
    favoriteFilters: []
  })

  // Core reactive state
  const status = ref<SearchStatus>('idle')
  const searchQuery = ref('') // What user sees in input
  const internalQuery = ref('') // What we actually search for
  const results = ref<any[]>([])
  const totalHits = ref(0)
  const facets = ref<DocumentFacet[]>([])
  const initialFacets = ref<DocumentFacet[]>([])
  
  // Pagination state
  const currentPage = ref(0)
  const totalPages = ref(0)
  const perPage = ref(24)

  // Filter state
  const filters = ref<DocumentSearchFilters>({
    query: '',
    tenants: [],
    contentTypes: [],
    languages: [],
    dateRange: {}
  })

  // Computed properties
  const isSearching = computed(() => status.value === 'searching')
  const isLoadingMore = computed(() => status.value === 'loading-more')
  const isLoadingFacets = computed(() => status.value === 'loading-facets')
  const hasError = computed(() => status.value === 'error')
  const hasResults = computed(() => results.value.length > 0)
  const hasMoreResults = computed(() => currentPage.value < totalPages.value)

  const hasActiveFilters = computed(() => {
    return filters.value.tenants.length > 0 ||
           filters.value.contentTypes.length > 0 ||
           filters.value.languages.length > 0 ||
           !!filters.value.dateRange.preset ||
           filters.value.dateRange.from || 
           filters.value.dateRange.to
  })

  // State mutation methods
  const setStatus = (newStatus: SearchStatus) => {
    status.value = newStatus
  }

  const setSearchQuery = (query: string) => {
    searchQuery.value = query
    filters.value.query = query
  }

  const setInternalQuery = (query: string) => {
    internalQuery.value = query
  }

  const setResults = (newResults: any[], isLoadMore = false) => {
    if (isLoadMore) {
      results.value = [...results.value, ...newResults]
      currentPage.value = currentPage.value + 1
    } else {
      results.value = newResults
      currentPage.value = 1
    }
  }

  const setTotalHits = (total: number) => {
    totalHits.value = total
    totalPages.value = Math.ceil(total / perPage.value)
  }

  const setFacets = (newFacets: DocumentFacet[]) => {
    facets.value = newFacets
  }

  const setInitialFacets = (newFacets: DocumentFacet[]) => {
    initialFacets.value = newFacets
    // If no current facets, use initial facets as default
    if (facets.value.length === 0) {
      facets.value = [...newFacets]
    }
  }

  const clearResults = () => {
    results.value = []
    totalHits.value = 0
    facets.value = []
    currentPage.value = 0
    totalPages.value = 0
  }

  const reset = () => {
    setStatus('idle')
    clearResults()
    searchQuery.value = ''
    internalQuery.value = ''
    filters.value = {
      query: '',
      tenants: [],
      contentTypes: [],
      languages: [],
      dateRange: {}
    }
  }

  // Recent searches management
  const addToRecentSearches = (query: string) => {
    const trimmed = query.trim()
    if (trimmed.length < 3) return

    const recent = preferences.value.recentSearches.filter(s => 
      s.toLowerCase() !== trimmed.toLowerCase()
    )
    
    preferences.value.recentSearches = [trimmed, ...recent].slice(0, 10)
  }

  const clearRecentSearches = () => {
    preferences.value.recentSearches = []
  }

  // View mode management
  const setViewMode = (mode: 'grid' | 'list') => {
    preferences.value.viewMode = mode
  }

  return {
    // State
    status: status as Ref<SearchStatus>,
    searchQuery,
    internalQuery,
    results,
    totalHits,
    facets,
    initialFacets,
    currentPage,
    totalPages,
    perPage,
    filters,
    preferences,

    // Computed
    isSearching,
    isLoadingMore,
    isLoadingFacets,
    hasError,
    hasResults,
    hasMoreResults,
    hasActiveFilters,

    // Methods
    setStatus,
    setSearchQuery,
    setInternalQuery,
    setResults,
    setTotalHits,
    setFacets,
    setInitialFacets,
    clearResults,
    reset,
    addToRecentSearches,
    clearRecentSearches,
    setViewMode
  }
}

export type SearchStateController = ReturnType<typeof useSearchState>
