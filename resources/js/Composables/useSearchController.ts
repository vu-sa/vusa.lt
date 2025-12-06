import { ref, computed, watch, type Ref } from 'vue'
import { useLocalStorage } from '@vueuse/core'

// Content type definitions
export interface ContentType {
  id: string
  name: string
  icon: string
  color: string
  indexName: string
  enabled: boolean
  order: number
}

export interface SearchPreferences {
  enabledTypes: string[]
  resultOrder: 'relevance' | 'date' | 'type'
  groupResults: boolean
  recentSearches: string[]
}

export interface SearchState {
  query: string
  selectedTypes: ContentType[]
  resultOrder: 'relevance' | 'date' | 'type'
  groupResults: boolean
  isSearching: boolean
  hasResults: boolean
}

export const useSearchController = () => {
  // Default content types configuration
  const defaultContentTypes: ContentType[] = [
    {
      id: 'news',
      name: 'News',
      icon: '📰',
      color: 'blue',
      indexName: 'news',
      enabled: true,
      order: 1
    },
    {
      id: 'pages',
      name: 'Pages', 
      icon: '📄',
      color: 'green',
      indexName: 'pages',
      enabled: true,
      order: 2
    },
    {
      id: 'publicInstitutions',
      name: 'Institutions',
      icon: '🏢',
      color: 'indigo',
      indexName: 'public_institutions',
      enabled: true,
      order: 3
    },
    {
      id: 'documents',
      name: 'Documents',
      icon: '📜',
      color: 'purple', 
      indexName: 'documents',
      enabled: true,
      order: 4
    },
    {
      id: 'calendar',
      name: 'Events',
      icon: '📅',
      color: 'amber',
      indexName: 'calendar',
      enabled: true,
      order: 5
    }
  ]

  // Persistent user preferences
  const preferences = useLocalStorage<SearchPreferences>('typesense-search-preferences', {
    enabledTypes: defaultContentTypes.map(t => t.id),
    resultOrder: 'relevance',
    groupResults: true,
    recentSearches: []
  })

  // Reactive state
  const searchQuery = ref('')
  const contentTypes = ref<ContentType[]>([...defaultContentTypes])
  const isSearching = ref(false)
  const resultCounts = ref<Record<string, number>>({})

  // Apply user preferences to content types
  const applyPreferences = () => {
    contentTypes.value = contentTypes.value.map(type => ({
      ...type,
      enabled: preferences.value.enabledTypes.includes(type.id)
    }))
  }

  // Initialize preferences
  applyPreferences()

  // Computed properties
  const selectedTypes = computed(() => 
    contentTypes.value.filter(type => type.enabled)
  )

  const orderedTypes = computed(() => {
    const types = [...selectedTypes.value]
    
    switch (preferences.value.resultOrder) {
      case 'date':
        // For date ordering, we might want to prioritize time-based content
        return types.sort((a, b) => {
          const dateRelevantTypes = ['news', 'calendar', 'documents']
          const aRelevant = dateRelevantTypes.includes(a.id) ? 0 : 1
          const bRelevant = dateRelevantTypes.includes(b.id) ? 0 : 1
          return aRelevant - bRelevant || a.order - b.order
        })
      
      case 'type':
        // Sort by predefined order
        return types.sort((a, b) => a.order - b.order)
      
      case 'relevance':
      default:
        // Sort by result count (most results first) or fall back to order
        return types.sort((a, b) => {
          const aCount = resultCounts.value[a.id] || 0
          const bCount = resultCounts.value[b.id] || 0
          if (aCount !== bCount) {
            return bCount - aCount // Descending by count
          }
          return a.order - b.order // Fall back to original order
        })
    }
  })

  const hasActiveResults = computed(() => 
    searchQuery.value.length >= 3 && selectedTypes.value.length > 0
  )

  const totalResultCount = computed(() => {
    // Sum up actual total hits, not just displayed results
    return Object.keys(resultCounts.value)
      .filter(key => key.endsWith('_total'))
      .reduce((sum, key) => sum + (resultCounts.value[key] || 0), 0)
  })

  const getDisplayedResultCount = computed(() => 
    Object.keys(resultCounts.value)
      .filter(key => !key.endsWith('_total'))
      .reduce((sum, key) => sum + (resultCounts.value[key] || 0), 0)
  )

  // Actions
  const toggleContentType = (typeId: string) => {
    const type = contentTypes.value.find(t => t.id === typeId)
    if (type) {
      type.enabled = !type.enabled
      
      // Update preferences
      preferences.value.enabledTypes = contentTypes.value
        .filter(t => t.enabled)
        .map(t => t.id)
    }
  }

  const setResultOrder = (order: 'relevance' | 'date' | 'type') => {
    preferences.value.resultOrder = order
  }

  const toggleGroupResults = () => {
    preferences.value.groupResults = !preferences.value.groupResults
  }

  const updateResultCount = (typeId: string, count: number) => {
    resultCounts.value[typeId] = count
  }

  const updateTotalResultCount = (typeId: string, totalHits: number) => {
    // For tracking actual total available results (not just displayed)
    resultCounts.value[`${typeId}_total`] = totalHits
  }

  const addRecentSearch = (query: string) => {
    if (!query.trim() || query.length < 3) return
    
    // Remove if already exists
    const filtered = preferences.value.recentSearches.filter(
      search => search.toLowerCase() !== query.toLowerCase()
    )
    
    // Add to beginning and limit to 10
    preferences.value.recentSearches = [query, ...filtered].slice(0, 10)
  }

  const clearRecentSearches = () => {
    preferences.value.recentSearches = []
  }

  const resetToDefaults = () => {
    preferences.value.enabledTypes = defaultContentTypes.map(t => t.id)
    preferences.value.resultOrder = 'relevance'
    preferences.value.groupResults = true
    applyPreferences()
  }

  // Watch for query changes to track searches
  watch(searchQuery, (newQuery, oldQuery) => {
    if (newQuery && newQuery !== oldQuery && newQuery.length >= 3) {
      // Debounced add to recent searches
      setTimeout(() => {
        if (searchQuery.value === newQuery) {
          addRecentSearch(newQuery)
        }
      }, 2000)
    }
  })

  // Watch preferences changes to apply them
  watch(() => preferences.value.enabledTypes, applyPreferences, { deep: true })

  // Search state object
  const searchState = computed<SearchState>(() => ({
    query: searchQuery.value,
    selectedTypes: selectedTypes.value,
    resultOrder: preferences.value.resultOrder,
    groupResults: preferences.value.groupResults,
    isSearching: isSearching.value,
    hasResults: totalResultCount.value > 0
  }))

  return {
    // State
    searchQuery,
    contentTypes: computed(() => contentTypes.value),
    selectedTypes,
    orderedTypes,
    preferences: computed(() => preferences.value),
    searchState,
    isSearching,
    resultCounts: computed(() => resultCounts.value),
    hasActiveResults,
    totalResultCount,
    getDisplayedResultCount,
    
    // Actions
    toggleContentType,
    setResultOrder,
    toggleGroupResults,
    updateResultCount,
    updateTotalResultCount,
    addRecentSearch,
    clearRecentSearches,
    resetToDefaults,
    setSearching: (searching: boolean) => { isSearching.value = searching }
  }
}

export type SearchController = ReturnType<typeof useSearchController>