/**
 * Admin Search Types
 *
 * Type definitions for the admin faceted search system.
 * These types support tenant-scoped search with facets across different collections.
 */

// Re-export existing search result types from useAdminSearch
export type {
  MeetingSearchResult,
  AgendaItemSearchResult,
  NewsSearchResult,
  PageSearchResult,
  CalendarSearchResult,
  InstitutionSearchResult,
  DocumentSearchResult,
} from '@/Composables/useAdminSearch'

/**
 * Available admin collections for search
 */
export type AdminCollection =
  | 'meetings'
  | 'agenda_items'
  | 'news'
  | 'pages'
  | 'calendar'
  | 'institutions'
  | 'documents'

/**
 * Facet value with count and selection state
 */
export interface AdminFacetValue {
  value: string
  count: number
  isSelected?: boolean
  label?: string // Optional display label (for content types, etc.)
}

/**
 * A single facet with its field and values
 */
export interface AdminFacet {
  field: string
  label: string
  values: AdminFacetValue[]
  type: FacetType
  icon?: string // Lucide icon name for display
}

/**
 * Types of facet UI components
 */
export type FacetType =
  | 'checkbox'      // Standard checkbox list
  | 'year-pills'    // Horizontal year pills
  | 'radio'         // Single selection radio
  | 'date-range'    // Date range picker

/**
 * Base filter structure for admin search
 */
export interface AdminSearchFilters {
  query: string
  [key: string]: string | string[] | number | number[] | DateRange | undefined
}

/**
 * Meeting-specific filters
 */
export interface MeetingSearchFilters extends AdminSearchFilters {
  year?: number[]
  completionStatus?: string[]
  voteAlignmentStatus?: string[]
  institutionType?: string[]
  tenantShortnames?: string[]
  isPublic?: boolean
}

/**
 * Agenda item-specific filters
 */
export interface AgendaItemSearchFilters extends AdminSearchFilters {
  meetingYear?: number[]
  studentVote?: string[]
  decision?: string[]
  isComplete?: boolean
  broughtByStudents?: boolean
  voteAlignmentStatus?: string[]
  tenantShortnames?: string[]
}

/**
 * Date range filter value
 */
export interface DateRange {
  from?: Date | string
  to?: Date | string
  preset?: 'recent' | 'last-month' | 'last-quarter' | 'last-year' | 'custom'
}

/**
 * Search status states
 */
export type AdminSearchStatus =
  | 'idle'
  | 'searching'
  | 'loading-facets'
  | 'loading-more'
  | 'error'

/**
 * Search error with user-friendly message
 */
export interface AdminSearchError {
  type: 'network' | 'auth' | 'rate-limit' | 'server' | 'unknown'
  message: string
  userMessage: string
  code?: string
  retryable: boolean
}

/**
 * Complete search state for a collection
 */
export interface AdminSearchState<T = unknown> {
  status: AdminSearchStatus
  query: string
  results: T[]
  totalHits: number
  facets: AdminFacet[]
  initialFacets: AdminFacet[]
  currentPage: number
  totalPages: number
  perPage: number
  error: AdminSearchError | null
}

/**
 * Search response from Typesense
 */
export interface AdminSearchResponse<T = unknown> {
  hits: T[]
  totalHits: number
  facets: AdminFacet[]
  page: number
  totalPages: number
}

/**
 * Facet configuration for a collection
 */
export interface FacetFieldConfig {
  field: string           // Typesense field name
  label: string           // Display label (i18n key)
  type: FacetType         // UI component type
  icon?: string           // Lucide icon name
  defaultOpen?: boolean   // Whether accordion is open by default
  maxValues?: number      // Max values to show before "show more"
  sortBy?: 'count' | 'alpha' // How to sort facet values
}

/**
 * Complete facet configuration for a collection
 */
export interface CollectionFacetConfig {
  facetBy: string                     // Comma-separated Typesense facet_by string
  fields: FacetFieldConfig[]          // Field configurations
  queryBy: string                     // Typesense query_by string
  defaultSortBy: string               // Default sort_by for this collection
}

/**
 * URL search params structure for state persistence
 */
export interface AdminSearchUrlParams {
  q?: string                          // Search query
  page?: string                       // Current page
  sort?: string                       // Sort order
  [key: string]: string | undefined   // Dynamic facet filters (e.g., year=2024, status=complete)
}

/**
 * Sort option configuration
 */
export interface SortOption {
  value: string           // Typesense sort_by value
  label: string           // Display label
  icon?: string           // Optional icon
}

/**
 * Card action configuration
 */
export interface SearchCardAction {
  type: 'view' | 'edit' | 'delete'
  label: string
  icon: string
  route?: string
  permission?: string     // Permission required (e.g., 'meetings.update.padalinys')
  variant?: 'default' | 'ghost' | 'destructive'
}

/**
 * Props for collection search cards
 */
export interface SearchCardProps<T> {
  result: T
  actions?: SearchCardAction[]
  isCompact?: boolean
}

/**
 * Emits for search cards
 */
export interface SearchCardEmits {
  (e: 'action', action: SearchCardAction, id: string): void
}

/**
 * Composable return type for useAdminCollectionSearch
 */
export interface AdminCollectionSearchController<T = unknown> {
  // State
  state: AdminSearchState<T>
  filters: AdminSearchFilters

  // Computed
  isSearching: boolean
  isLoadingFacets: boolean
  isLoadingMore: boolean
  hasResults: boolean
  hasActiveFilters: boolean
  hasMoreResults: boolean
  mergedFacets: AdminFacet[]
  activeFilterCount: number

  // Actions
  search: (query: string, immediate?: boolean) => Promise<void>
  setFilter: (field: string, value: unknown) => void
  toggleFilter: (field: string, value: string) => void
  clearFilters: () => void
  loadMore: () => Promise<void>
  setSortBy: (sortBy: string) => void
  refresh: () => Promise<void>

  // URL state
  syncToUrl: () => void
  loadFromUrl: () => void
}
