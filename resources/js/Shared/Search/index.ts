/**
 * Shared Search Module
 *
 * Provides common search functionality for both admin and public contexts.
 * This module includes:
 * - Shared types for search state, filters, facets, and errors
 * - Utility services for facet merging, filter manipulation, and error handling
 * - Base search composable that can be extended by entity-specific implementations
 * - Search client factory for creating Typesense clients
 *
 * @example
 * ```typescript
 * import { useBaseSearch, FilterUtils, FacetMerger } from '@/Shared/Search'
 *
 * // Use the base search composable
 * const search = useBaseSearch({
 *   defaultFilters: { query: '', tenants: [] },
 *   searchService: mySearchService,
 *   filterKeyMapper: myMapper
 * })
 *
 * // Use utilities directly
 * const hasFilters = FilterUtils.hasActiveFilters(filters)
 * const merged = FacetMerger.mergeSimple(initial, current, filters)
 * ```
 */

// Types
export * from './types';

// Services
export * from './services';

// Composables
export * from './composables';
