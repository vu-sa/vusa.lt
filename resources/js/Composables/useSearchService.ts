import { ref, computed, watch, onUnmounted, nextTick } from 'vue'
import { debounce } from 'lodash-es'
import type { ContentType } from './useSearchController'

export interface SearchResult {
  id: string
  type: string
  title: string
  name?: string
  contentType: ContentType
  [key: string]: any
}

export interface ContentTypeResults {
  contentType: ContentType
  results: SearchResult[]
  totalHits: number
  isLastPage: boolean
  refineNext: () => void
}

export interface SearchServiceState {
  isSearching: boolean
  searchQuery: string
  resultsByType: Record<string, ContentTypeResults>
  totalHits: number
}

export const useSearchService = () => {
  // Core state
  const isSearching = ref(false)
  const searchQuery = ref('')
  const resultsByType = ref<Record<string, ContentTypeResults>>({})
  const searchClient = ref<any>(null)
  const instantSearchInstance = ref<any>(null)
  
  // Request cancellation
  const abortController = ref<AbortController | null>(null)
  
  // Initialize search client (this will be set by the main search component)
  const initializeSearchClient = (client: any) => {
    searchClient.value = client
  }
  
  // Initialize InstantSearch instance
  const initializeInstantSearch = (instance: any) => {
    instantSearchInstance.value = instance
  }
  
  // Centralized search function with proper debouncing
  const debouncedSearch = debounce(async (query: string, enabledTypes: ContentType[]) => {
    // Cancel previous request
    if (abortController.value) {
      abortController.value.abort()
    }
    
    // Clear results if query is too short
    if (query.length < 3) {
      resultsByType.value = {}
      isSearching.value = false
      return
    }
    
    isSearching.value = true
    abortController.value = new AbortController()
    
    try {
      // Clear previous results for disabled types
      const enabledTypeIds = new Set(enabledTypes.map(t => t.id))
      Object.keys(resultsByType.value).forEach(typeId => {
        if (!enabledTypeIds.has(typeId)) {
          delete resultsByType.value[typeId]
        }
      })
      
      // Let InstantSearch handle the actual searching
      // This service just coordinates the state
      await nextTick()
      isSearching.value = false
      
    } catch (error) {
      if (error.name !== 'AbortError') {
        console.error('Search error:', error)
      }
      isSearching.value = false
    }
  }, 300)
  
  // Public search function
  const search = (query: string, enabledTypes: ContentType[]) => {
    searchQuery.value = query
    debouncedSearch(query, enabledTypes)
  }
  
  // Update results for a specific content type
  const updateContentTypeResults = (
    contentTypeId: string,
    contentType: ContentType,
    results: SearchResult[],
    totalHits: number,
    isLastPage: boolean,
    refineNext: () => void
  ) => {
    resultsByType.value[contentTypeId] = {
      contentType,
      results: results.map(result => ({
        ...result,
        type: contentTypeId,
        contentType
      })),
      totalHits,
      isLastPage,
      refineNext
    }
  }
  
  // Clear results for a specific content type
  const clearContentTypeResults = (contentTypeId: string) => {
    delete resultsByType.value[contentTypeId]
  }
  
  // Clear all results
  const clearAllResults = () => {
    resultsByType.value = {}
    searchQuery.value = ''
    isSearching.value = false
  }
  
  // Get results for a specific content type
  const getContentTypeResults = (contentTypeId: string): ContentTypeResults | null => {
    return resultsByType.value[contentTypeId] || null
  }
  
  // Get unified results across all content types
  const getUnifiedResults = (
    enabledTypes: ContentType[],
    resultOrder: 'relevance' | 'date' | 'type' = 'relevance',
    maxResults: number = 50
  ): SearchResult[] => {
    const allResults: SearchResult[] = []
    
    // Collect results from enabled content types
    enabledTypes.forEach(contentType => {
      const typeResults = resultsByType.value[contentType.id]
      if (typeResults) {
        allResults.push(...typeResults.results)
      }
    })
    
    // Sort based on result order preference
    let sortedResults = [...allResults]
    
    switch (resultOrder) {
      case 'date':
        sortedResults.sort((a, b) => {
          const aDate = getItemDate(a)
          const bDate = getItemDate(b)
          if (!aDate && !bDate) return 0
          if (!aDate) return 1
          if (!bDate) return -1
          
          const aTime = typeof aDate === 'number' ? aDate : new Date(aDate).getTime()
          const bTime = typeof bDate === 'number' ? bDate : new Date(bDate).getTime()
          
          return bTime - aTime // Most recent first
        })
        break
        
      case 'type':
        sortedResults.sort((a, b) => {
          const aOrder = a.contentType?.order || 999
          const bOrder = b.contentType?.order || 999
          if (aOrder !== bOrder) return aOrder - bOrder
          return 0
        })
        break
        
      case 'relevance':
      default:
        sortedResults.sort((a, b) => {
          const aScore = a._score || 0
          const bScore = b._score || 0
          return bScore - aScore
        })
        break
    }
    
    return sortedResults.slice(0, maxResults)
  }
  
  // Helper function to get item date
  const getItemDate = (item: SearchResult) => {
    switch (item.type) {
      case 'news': return item.publish_time
      case 'documents': return item.document_date
      case 'calendar': return item.date
      case 'publicInstitutions': return item.updated_at
      default: return item.created_at
    }
  }
  
  // Computed properties
  const totalHits = computed(() => {
    return Object.values(resultsByType.value).reduce((sum, typeResult) => {
      return sum + (typeResult.totalHits || 0)
    }, 0)
  })
  
  const hasResults = computed(() => {
    return Object.keys(resultsByType.value).length > 0 && totalHits.value > 0
  })
  
  const hasMoreResults = computed(() => {
    return Object.values(resultsByType.value).some(typeResult => !typeResult.isLastPage)
  })
  
  // Load more results for content types that have more available
  const loadMoreResults = () => {
    // Find the content type with the fewest results to load more from
    // This helps maintain balance across content types
    let minResults = Infinity
    let targetTypeResult: ContentTypeResults | null = null
    
    Object.values(resultsByType.value).forEach(typeResult => {
      if (!typeResult.isLastPage && typeResult.results.length < minResults) {
        minResults = typeResult.results.length
        targetTypeResult = typeResult
      }
    })
    
    if (targetTypeResult) {
      targetTypeResult.refineNext()
    }
  }
  
  // Service state object
  const serviceState = computed<SearchServiceState>(() => ({
    isSearching: isSearching.value,
    searchQuery: searchQuery.value,
    resultsByType: resultsByType.value,
    totalHits: totalHits.value
  }))
  
  // Cleanup on unmount
  onUnmounted(() => {
    if (abortController.value) {
      abortController.value.abort()
    }
    // Cancel any pending debounced searches
    debouncedSearch.cancel()
  })
  
  return {
    // State
    serviceState,
    isSearching: computed(() => isSearching.value),
    hasResults,
    totalHits,
    hasMoreResults,
    searchQuery: computed(() => searchQuery.value),
    
    // Methods
    initializeSearchClient,
    initializeInstantSearch,
    search,
    updateContentTypeResults,
    clearContentTypeResults,
    clearAllResults,
    getContentTypeResults,
    getUnifiedResults,
    loadMoreResults,
    
    // Internal state for components that need direct access
    resultsByType: computed(() => resultsByType.value)
  }
}

export type SearchService = ReturnType<typeof useSearchService>