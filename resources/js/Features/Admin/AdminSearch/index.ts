/**
 * Admin Search Feature
 *
 * Provides faceted search capabilities for admin collections.
 */

// Types
export * from './Types/AdminSearchTypes';

// Services
export * from './Services/AdminSearchService';
export * from './Services/AdminFacetMerger';

// Config
export * from './Config/collectionFacetConfig';

// Composables
export { useAdminCollectionSearch } from './Composables/useAdminCollectionSearch';
export type { AdminCollectionSearchController } from './Composables/useAdminCollectionSearch';
