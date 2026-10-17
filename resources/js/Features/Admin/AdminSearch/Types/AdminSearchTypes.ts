/**
 * Available admin collections for search
 */
export type AdminCollection
  = | 'meetings'
    | 'agenda_items'
    | 'news'
    | 'pages'
    | 'calendar'
    | 'institutions'
    | 'documents'
    | 'resources'
    | 'duties'
    | 'users';

/**
 * Facet value with count and selection state
 */
export interface AdminFacetValue {
  value: string;
  count: number | null;
  isSelected?: boolean;
  label?: string; // Optional display label (for content types, etc.)
}

/**
 * A single facet with its field and values
 */
export interface AdminFacet {
  field: string;
  label: string;
  values: AdminFacetValue[];
  type: FacetType;
  icon?: string; // Lucide icon name for display
}

/**
 * Types of facet UI components
 */
export type FacetType
  = | 'checkbox' // Standard checkbox list
    | 'year-pills' // Horizontal year pills
    | 'radio' // Single selection radio
    | 'date-range'; // Date range picker

/**
 * Base filter structure for admin search
 */
export interface AdminSearchFilters {
  query: string;
  [key: string]: string | string[] | number | number[] | DateRange | undefined;
}

/**
 * Date range filter value
 */
export interface DateRange {
  from?: Date | string;
  to?: Date | string;
  preset?: 'recent' | 'last-month' | 'last-quarter' | 'last-year' | 'custom';
}

/**
 * Search status states
 */
export type AdminSearchStatus
  = | 'idle'
    | 'searching'
    | 'loading-facets'
    | 'loading-more'
    | 'error';

/**
 * Search error with user-friendly message
 */
export interface AdminSearchError {
  type: 'network' | 'auth' | 'rate-limit' | 'server' | 'unknown';
  message: string;
  userMessage: string;
  code?: string;
  retryable: boolean;
}

/**
 * Facet configuration for a collection
 */
export interface FacetFieldConfig {
  field: string; // Typesense field name
  label: string; // Display label (i18n key)
  type: FacetType; // UI component type
  icon?: string; // Lucide icon name
  defaultOpen?: boolean; // Whether accordion is open by default
  maxValues?: number; // Max values to show before "show more"
  sortBy?: 'count' | 'alpha'; // How to sort facet values
}

/**
 * Complete facet configuration for a collection
 */
export interface CollectionFacetConfig {
  facetBy: string; // Comma-separated Typesense facet_by string
  fields: FacetFieldConfig[]; // Field configurations
  queryBy: string; // Typesense query_by string
  defaultSortBy: string; // Default sort_by for this collection
}

/**
 * Sort option configuration
 */
export interface SortOption {
  value: string; // Typesense sort_by value
  label: string; // Display label
  icon?: string; // Optional icon
}
