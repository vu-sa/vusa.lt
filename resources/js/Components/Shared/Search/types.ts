/**
 * Shared types for search filter components
 *
 * These types are used by both admin and public search interfaces.
 */

/**
 * A filter option with value, label, and optional count
 */
export interface FilterOption {
  value: string | number
  label: string
  count?: number
}

/**
 * Facet value from search engine (Meilisearch/Typesense)
 */
export interface FacetValue {
  value: string
  count: number
}

/**
 * Date range filter value
 */
export interface DateRangeValue {
  from?: Date
  to?: Date
  preset?: string
}

/**
 * Sort option for search results
 */
export interface SortOption {
  value: string
  label: string
}

/**
 * Search error with user-friendly message
 */
export interface SearchError {
  message: string
  userMessage: string
  retryable: boolean
  code?: string
}

/**
 * Filter accordion item configuration
 */
export interface FilterAccordionConfig {
  value: string
  label: string
  description?: string
  icon: import('vue').Component
  iconContainerClass?: string
}

/**
 * View mode option for results display
 */
export interface ViewModeOption {
  value: string
  label: string
  icon: string
}
