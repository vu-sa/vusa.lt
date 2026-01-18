/**
 * Document Search Composable V2
 *
 * Refactored version using shared search infrastructure.
 * This composable extends useBaseSearch with document-specific functionality.
 */

import { ref, computed, nextTick, type Ref, type ComputedRef } from 'vue'
import { useLocalStorage } from '@vueuse/core'
import { usePage } from '@inertiajs/vue3'
import { createTypesenseClients } from './useSearchClient'

import { useBaseSearch, type BaseSearchService } from '@/Shared/Search/composables/useBaseSearch'
import { FilterUtils } from '@/Shared/Search/services/FilterUtils'
import type { ProcessedSearchResult } from '@/Shared/Search/types'

import type {
  DocumentSearchFilters,
  DocumentFacet,
  DocumentSearchPreferences,
  DocumentSearchController,
  SearchState,
  SearchError,
  SearchStatus
} from '../Types/DocumentSearchTypes'
import { DocumentSearchService } from '../Services/DocumentSearchService'

// Re-export types for backward compatibility
export type {
  DocumentSearchFilters,
  DocumentFacet
}

// ============================================================================
// Types
// ============================================================================

/**
 * Extended preferences for document search
 */
interface DocumentPreferences extends DocumentSearchPreferences {
  viewMode: 'list' | 'compact'
  recentSearches: string[]
}

// ============================================================================
// Service Adapter
// ============================================================================

/**
 * Adapter to make DocumentSearchService compatible with BaseSearchService interface
 */
class DocumentSearchServiceAdapter implements BaseSearchService<DocumentSearchFilters, DocumentFacet> {
  private service: DocumentSearchService

  constructor(service: DocumentSearchService) {
    this.service = service
  }

  async performSearch(
    filters: DocumentSearchFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number
  ): Promise<ProcessedSearchResult<DocumentFacet>> {
    const result = await this.service.performSearch(filters, perPage, isLoadMore, currentPage)
    return {
      hits: result.hits,
      totalHits: result.totalHits,
      totalPages: result.totalPages,
      currentPage: result.currentPage,
      facets: result.facets
    }
  }

  async loadInitialFacets(): Promise<DocumentFacet[]> {
    return this.service.loadInitialFacets()
  }

  cancelCurrentSearch(): void {
    this.service.cancelCurrentSearch()
  }
}

// ============================================================================
// Composable
// ============================================================================

export const useDocumentSearch = (): DocumentSearchController => {
  // Extended preferences for document search
  const documentPreferences = useLocalStorage<DocumentPreferences>('document-search-preferences', {
    viewMode: 'list',
    recentSearches: []
  })

  // Search client refs (for initialization)
  const searchClient = ref<any>(null)
  const typesenseClient = ref<any>(null)
  const documentService = ref<DocumentSearchService | null>(null)

  // Default filters for document search
  const defaultFilters: DocumentSearchFilters = {
    query: '',
    tenants: [],
    contentTypes: [],
    languages: [],
    dateRange: {}
  }

  // Filter key mapper for document facets
  const filterKeyMapper = (field: string, filters: DocumentSearchFilters): string[] => {
    switch (field) {
      case 'tenant_shortname':
        return filters.tenants
      case 'content_type':
        return filters.contentTypes
      case 'language':
        return filters.languages
      default:
        return []
    }
  }

  // Initialize base search without service (will be set after client init)
  const baseSearch = useBaseSearch<DocumentSearchFilters, DocumentFacet, any>({
    defaultFilters,
    searchService: null, // Will be set after initialization
    filterKeyMapper,
    localStorageKey: 'document-search-state',
    perPage: 24,
    debounceDelay: 300,
    maxRetries: 3,
    searchOnMount: false,
    loadFacetsOnMount: false
  })

  // ============================================================================
  // Client Initialization
  // ============================================================================

  const initializeSearchClient = async () => {
    const page = usePage()
    const typesenseConfig = page.props.typesenseConfig as any

    if (!typesenseConfig?.apiKey) {
      console.warn('Typesense not configured - document search unavailable')
      return null
    }

    try {
      const clients = createTypesenseClients(typesenseConfig, {
        additionalSearchParameters: {
          query_by: 'title,summary',
          num_typos: 2,
          typo_tokens_threshold: 1,
          drop_tokens_threshold: 1,
          max_hits: 1000,
          per_page: 20,
          facet_by: [
            'content_type',
            'tenant_shortname',
            'language',
            'document_date'
          ].join(','),
          max_facet_values: 50,
          sort_by: 'document_date:desc,created_at:desc'
        },
        collectionSpecificSearchParameters: {
          documents: {
            query_by: 'title,summary',
            facet_by: [
              'content_type',
              'tenant_shortname',
              'language',
              'document_date'
            ].join(','),
            per_page: 24
          }
        }
      })

      searchClient.value = clients.searchClient
      typesenseClient.value = clients.typesenseClient

      // Create document service and adapter
      documentService.value = new DocumentSearchService(typesenseClient.value)
      const adapter = new DocumentSearchServiceAdapter(documentService.value)

      // Set the service on base search
      baseSearch.setSearchService(adapter)

      // Load initial facets
      await nextTick()
      await baseSearch.loadInitialFacets()

      return clients.searchClient
    } catch (error) {
      console.error('Failed to initialize Typesense clients:', error)
      return null
    }
  }

  // ============================================================================
  // Document-Specific Methods
  // ============================================================================

  const toggleTenant = (tenantShortname: string) => {
    const current = baseSearch.filters.value.tenants
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      tenants: FilterUtils.toggleArrayValue(current, tenantShortname)
    }
    baseSearch.debouncedSearch()
  }

  const toggleContentType = (contentType: string) => {
    const current = baseSearch.filters.value.contentTypes
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      contentTypes: FilterUtils.toggleArrayValue(current, contentType)
    }
    baseSearch.debouncedSearch()
  }

  const toggleLanguage = (language: string) => {
    const current = baseSearch.filters.value.languages
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      languages: FilterUtils.toggleArrayValue(current, language)
    }
    baseSearch.debouncedSearch()
  }

  const setDateRange = (dateRange: { from?: Date; to?: Date; preset?: string }) => {
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      dateRange: {
        from: dateRange.from,
        to: dateRange.to,
        preset: dateRange.preset as DocumentSearchFilters['dateRange']['preset']
      }
    }
    baseSearch.debouncedSearch()
  }

  const setViewMode = (mode: 'list' | 'compact') => {
    documentPreferences.value.viewMode = mode
  }

  const clearFilters = () => {
    baseSearch.filters.value = {
      query: baseSearch.filters.value.query,
      tenants: [],
      contentTypes: [],
      languages: [],
      dateRange: {}
    }
    baseSearch.debouncedSearch()
  }

  // ============================================================================
  // Recent Searches (using document preferences)
  // ============================================================================

  const clearRecentSearches = () => {
    documentPreferences.value.recentSearches = []
  }

  const removeRecentSearch = (searchToRemove: string) => {
    documentPreferences.value.recentSearches = documentPreferences.value.recentSearches.filter(
      s => s !== searchToRemove
    )
  }

  // ============================================================================
  // Search State (matching original interface)
  // ============================================================================

  const searchState = computed<SearchState>(() => ({
    status: baseSearch.status.value,
    isSearching: baseSearch.status.value === 'searching',
    hasResults: baseSearch.hasResults.value,
    totalHits: baseSearch.totalHits.value,
    query: baseSearch.displayQuery.value,
    filters: baseSearch.filters.value,
    facets: baseSearch.facets.value,
    results: baseSearch.results.value,
    viewMode: documentPreferences.value.viewMode,
    error: baseSearch.error.value,
    isOnline: baseSearch.isOnline.value
  }))

  // ============================================================================
  // Return Controller (matching DocumentSearchController interface)
  // ============================================================================

  return {
    // State from base
    searchState,
    isSearching: baseSearch.isSearching,
    isLoadingFacets: baseSearch.isLoadingFacets,
    isLoadingMore: baseSearch.isLoadingMore,
    hasResults: baseSearch.hasResults,
    hasActiveFilters: baseSearch.hasActiveFilters,
    hasMoreResults: baseSearch.hasMoreResults,
    totalHits: baseSearch.totalHits,
    results: baseSearch.results,
    facets: baseSearch.mergedFacets, // Use merged facets
    filters: baseSearch.filters as unknown as ComputedRef<DocumentSearchFilters>,
    viewMode: computed(() => documentPreferences.value.viewMode),
    recentSearches: computed(() => documentPreferences.value.recentSearches),

    // Error handling
    searchError: baseSearch.error as ComputedRef<SearchError | null>,
    isOnline: baseSearch.isOnline,
    retryCount: baseSearch.retryCount,
    maxRetries: baseSearch.maxRetries,

    // Actions from base
    search: baseSearch.search,
    setFilter: baseSearch.setFilter as <K extends keyof DocumentSearchFilters>(key: K, value: DocumentSearchFilters[K]) => void,
    loadMore: baseSearch.loadMore,
    retrySearch: baseSearch.retrySearch,
    clearError: baseSearch.clearError,
    loadInitialFacets: baseSearch.loadInitialFacets,

    // Document-specific actions
    toggleTenant,
    toggleContentType,
    toggleLanguage,
    setDateRange,
    setViewMode,
    clearFilters,
    clearRecentSearches,
    removeRecentSearch,

    // Internal
    searchClient: computed(() => searchClient.value),
    initializeSearchClient
  }
}
