/**
 * Shared Search Types
 *
 * Common interfaces and types used across both admin and public search implementations.
 * Entity-specific types should extend these base types.
 */

// ============================================================================
// Core Search Types
// ============================================================================

/**
 * Search error handling - shared across all search types
 */
export interface SearchError {
  type: 'network' | 'server' | 'client' | 'timeout' | 'abort'
  message: string
  userMessage: string
  code?: string | number
  retryable: boolean
  timestamp: Date
}

/**
 * Search status states
 */
export type SearchStatus = 'idle' | 'searching' | 'loading-more' | 'loading-facets' | 'error'

/**
 * Sort direction
 */
export type SortDirection = 'asc' | 'desc'

/**
 * Sort option for search results
 */
export interface SortOption {
  value: string
  label: string
  field: string
  direction: SortDirection
}

// ============================================================================
// Facet Types
// ============================================================================

/**
 * Individual facet value
 */
export interface FacetValue {
  value: string
  label: string
  count: number
  highlighted?: string
  isSelected?: boolean
  level?: number // For hierarchical display
}

/**
 * Facet field type for UI rendering
 */
export type FacetType = 'checkbox' | 'radio' | 'year-pills' | 'date-range'

/**
 * Base facet structure - can be extended for model-specific facets
 */
export interface BaseFacet {
  field: string
  label: string
  type?: FacetType
  icon?: string
  values: FacetValue[]
}

/**
 * Facet configuration for a field
 */
export interface FacetFieldConfig {
  field: string
  label: string
  type: FacetType
  icon?: string
  collapsible?: boolean
  defaultExpanded?: boolean
}

/**
 * Collection facet configuration
 */
export interface FacetConfig {
  fields: FacetFieldConfig[]
  defaultFacets?: string[]
}

// ============================================================================
// Filter Types
// ============================================================================

/**
 * Date range filter - shared between all search types
 */
export interface DateRangeFilter {
  from?: Date
  to?: Date
  preset?: 'recent' | '3months' | '6months' | '1year' | 'year-range' | 'custom'
}

/**
 * Base filter structure - extend for model-specific filters
 */
export interface BaseSearchFilters {
  query: string
  tenants?: string[]
  [key: string]: unknown
}

// ============================================================================
// Search State Types
// ============================================================================

/**
 * Base search state - extend for model-specific state
 */
export interface BaseSearchState<TFilters extends BaseSearchFilters, TFacet extends BaseFacet> {
  status: SearchStatus
  hasResults: boolean
  totalHits: number
  query: string
  filters: TFilters
  facets: TFacet[]
  initialFacets: TFacet[]
  results: unknown[]
  error: SearchError | null
  isOnline: boolean
  currentPage: number
  totalPages: number
  perPage: number
}

/**
 * Base search preferences
 */
export interface BaseSearchPreferences {
  recentSearches: string[]
  viewMode?: 'list' | 'compact' | 'grid'
}

// ============================================================================
// Search Result Types
// ============================================================================

/**
 * Processed search result from service
 */
export interface ProcessedSearchResult<TFacet extends BaseFacet> {
  hits: unknown[]
  totalHits: number
  facets: TFacet[]
  currentPage: number
  totalPages: number
}

// ============================================================================
// Typesense Configuration
// ============================================================================

/**
 * Typesense node configuration
 */
export interface TypesenseNode {
  protocol: string
  host: string
  port: number
}

/**
 * Collection names mapping
 */
export interface TypesenseCollections {
  news?: string
  pages?: string
  documents?: string
  calendar?: string
  public_institutions?: string
  public_meetings?: string
  meetings?: string
  agenda_items?: string
  institutions?: string
  [key: string]: string | undefined
}

/**
 * Base Typesense configuration
 */
export interface TypesenseConfig {
  apiKey: string
  nodes: TypesenseNode[]
  collections?: TypesenseCollections
}

/**
 * Admin scoped collection config
 */
export interface ScopedCollectionConfig {
  key: string
  name: string
  tenantIds: number[]
  institutionIds: string[]
  directInstitutionIds: string[]
  hasAccess: boolean
  scope: 'all' | 'public' | 'padalinys' | 'own' | 'combined'
}

/**
 * Admin search configuration with scoped keys
 */
export interface AdminSearchConfig {
  collections: Record<string, ScopedCollectionConfig>
  headerKey: string
  nodes: TypesenseNode[]
  expiresAt: number
  isSuperAdmin: boolean
}

// ============================================================================
// Search Controller Interface
// ============================================================================

/**
 * Base search controller interface - minimum methods all search controllers should have
 */
export interface BaseSearchController<
  TFilters extends BaseSearchFilters,
  TFacet extends BaseFacet,
  TResult = unknown
> {
  // State refs
  status: { value: SearchStatus }
  query: { value: string }
  results: { value: TResult[] }
  facets: { value: TFacet[] }
  filters: { value: TFilters }
  totalHits: { value: number }
  error: { value: SearchError | null }
  isOnline: { value: boolean }

  // Pagination
  currentPage: { value: number }
  totalPages: { value: number }
  perPage: { value: number }

  // Computed
  isSearching: { value: boolean }
  isLoadingFacets: { value: boolean }
  isLoadingMore: { value: boolean }
  hasResults: { value: boolean }
  hasActiveFilters: { value: boolean }
  hasMoreResults: { value: boolean }
  activeFilterCount: { value: number }

  // Search methods
  search: (query: string, immediate?: boolean) => void
  loadMore: () => void
  refresh: () => void

  // Filter methods
  setFilter: (field: string, value: unknown) => void
  toggleFilter: (field: string, value: string | number) => void
  clearFilters: () => void

  // Error handling
  retrySearch: () => void
  clearError: () => void

  // Initialization
  initialize: () => Promise<void>
  loadInitialFacets: () => Promise<void>
}

// ============================================================================
// Search Service Interface
// ============================================================================

/**
 * Abstract search service interface
 * Implementations handle the actual Typesense API calls
 */
export interface SearchService<TFilters extends BaseSearchFilters, TFacet extends BaseFacet> {
  performSearch(
    filters: TFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number
  ): Promise<ProcessedSearchResult<TFacet>>

  loadInitialFacets(): Promise<TFacet[]>
}

// ============================================================================
// Client Factory Types
// ============================================================================

/**
 * Search client type - either public or admin scoped
 */
export type SearchClientType = 'public' | 'admin'

/**
 * Client factory options
 */
export interface SearchClientOptions {
  type: SearchClientType
  collection?: string
  additionalSearchParameters?: Record<string, unknown>
}

/**
 * Created search clients
 */
export interface SearchClients {
  typesenseClient: unknown
  searchClient?: unknown // InstantSearch adapter (optional)
}

// ============================================================================
// Shared Search Result Types (Admin & Public)
// ============================================================================

/**
 * Meeting search result - used in admin search
 */
export interface MeetingSearchResult {
  id: string
  title: string
  description?: string
  start_time?: number
  tenant_id?: number
  tenant_shortname?: string
  institution_name_lt?: string
  institution_name_en?: string
  institution_ids?: string[]
  completion_status?: string
}

/**
 * Agenda item search result
 */
export interface AgendaItemSearchResult {
  id: string
  title: string
  description?: string
  outcome?: string
  meeting_id?: string
  meeting_title?: string
  vote_result?: string
  meeting_start_time?: number
  institution_ids?: string[]
}

/**
 * News search result
 */
export interface NewsSearchResult {
  id: string
  title: string
  short?: string
  permalink: string
  image?: string
  publish_time?: number
  lang: string
  tenant_id?: number
  tenant_name?: string
}

/**
 * Page search result
 */
export interface PageSearchResult {
  id: string
  title: string
  permalink: string
  meta_description?: string
  lang: string
  tenant_id?: number
  tenant_name?: string
  category_name?: string
}

/**
 * Calendar event search result
 */
export interface CalendarSearchResult {
  id: string
  title: string
  title_lt?: string
  title_en?: string
  date: number
  end_date?: number
  lang?: string
  tenant_id?: number
  tenant_name?: string
}

/**
 * Institution search result
 */
export interface InstitutionSearchResult {
  id: string
  name_lt?: string
  name_en?: string
  short_name_lt?: string
  short_name_en?: string
  alias?: string
  email?: string
  tenant_id?: number
  tenant_shortname?: string
}

/**
 * Document search result
 */
export interface DocumentSearchResult {
  id: string
  title: string
  summary?: string
  name?: string
  language?: string
  content_type?: string
  institution_name_lt?: string
  institution_name_en?: string
  tenant_shortname?: string
  document_date?: number
  document_year?: string
  anonymous_url: string
  is_active: boolean
}

/**
 * Resource search result
 */
export interface ResourceSearchResult {
  id: string
  name_lt?: string
  name_en?: string
  description_lt?: string
  description_en?: string
  location?: string
  capacity?: number
  is_reservable: boolean
  tenant_id?: number
  tenant_shortname?: string
  category_id?: number
  category_name?: string
  image_url?: string
  created_at?: number
}

/**
 * Multi-search results container (admin search)
 */
export interface MultiSearchResults {
  meetings: MeetingSearchResult[]
  agendaItems: AgendaItemSearchResult[]
  news: NewsSearchResult[]
  pages: PageSearchResult[]
  calendar: CalendarSearchResult[]
  institutions: InstitutionSearchResult[]
  documents: DocumentSearchResult[]
}
