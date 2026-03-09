/**
 * Base search types shared across Document, Institution, and Meeting search
 * 
 * This file contains common interfaces that can be extended by model-specific search types.
 */

// Search error handling - shared across all search types
export interface SearchError {
  type: 'network' | 'server' | 'client' | 'timeout' | 'abort'
  message: string
  userMessage: string
  code?: string | number
  retryable: boolean
  timestamp: Date
}

// Search status - shared across all search types
export type SearchStatus = 'idle' | 'searching' | 'loading-more' | 'loading-facets' | 'error'

// Base facet structure - can be extended for model-specific facets
export interface BaseFacet {
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

// Base filter structure - extend for model-specific filters
export interface BaseSearchFilters {
  query: string
  tenants: string[]
}

// Base search state - extend for model-specific state
export interface BaseSearchState<TFilters extends BaseSearchFilters, TFacet extends BaseFacet> {
  status: SearchStatus
  hasResults: boolean
  totalHits: number
  query: string
  filters: TFilters
  facets: TFacet[]
  results: any[]
  error: SearchError | null
  isOnline: boolean
}

// Base search preferences
export interface BaseSearchPreferences {
  recentSearches: string[]
}

// Typesense configuration - shared
export interface TypesenseNode {
  protocol: string
  host: string
  port: number
}

export interface TypesenseCollections {
  news: string
  pages: string
  documents: string
  calendar: string
  public_institutions: string
  public_meetings: string
}

export interface TypesenseConfig {
  apiKey: string
  nodes: TypesenseNode[]
  collections?: TypesenseCollections
}

// Date range filter - shared between Document and Meeting search
export interface DateRangeFilter {
  from?: Date
  to?: Date
  preset?: 'recent' | '3months' | '6months' | '1year' | 'year-range' | 'custom'
}

// Base search controller interface - minimum methods all search controllers should have
export interface BaseSearchController<TFilters extends BaseSearchFilters, TFacet extends BaseFacet> {
  // State
  searchState: { value: BaseSearchState<TFilters, TFacet> }
  searchError: { value: SearchError | null }
  isOnline: { value: boolean }
  retryCount: { value: number }
  maxRetries: number
  
  // Computed
  isSearching: { value: boolean }
  isLoadingFacets: { value: boolean }
  isLoadingMore: { value: boolean }
  hasResults: { value: boolean }
  hasActiveFilters: { value: boolean }
  hasMoreResults: { value: boolean }
  totalHits: { value: number }
  results: { value: any[] }
  facets: { value: TFacet[] }
  filters: { value: TFilters }
  recentSearches: { value: string[] }
  
  // Methods
  search: (query: string, isInitial?: boolean) => Promise<void>
  toggleTenant: (tenant: string) => void
  clearFilters: () => void
  loadMore: () => Promise<void>
  retrySearch: () => Promise<void>
  clearError: () => void
  loadInitialFacets: () => Promise<void>
  initializeSearchClient: () => Promise<any>
  addToRecentSearches: (query: string) => void
  removeFromRecentSearches: (query: string) => void
  clearRecentSearches: () => void
}
