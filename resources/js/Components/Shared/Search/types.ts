/**
 * Shared types for search filter components
 *
 * These types are used by both admin and public search interfaces.
 */

/**
 * A filter option with value, label, and optional count
 */
export interface FilterOption {
  value: string | number;
  label: string;
  count?: number | null;
}

/**
 * Facet value from search engine (Meilisearch/Typesense)
 */
export interface FacetValue {
  value: string;
  count: number | null;
}
