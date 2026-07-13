import type { ComputedRef, Ref } from 'vue';

import type {
  AdminFacet,
  AdminSearchError,
  AdminSearchFilters,
  CollectionFacetConfig,
  SortOption,
} from '../../Types/AdminSearchTypes';

/**
 * The reactive surface the record selector needs from a search backend.
 *
 * `useAdminCollectionSearch` structurally satisfies this, so `SearchSelectView`
 * stays agnostic about which collection (or scope) drives the results.
 */
export interface SelectSearchController {
  facetConfig: CollectionFacetConfig;
  sortOptions: Ref<SortOption[]> | ComputedRef<SortOption[]>;
  query: Ref<string>;
  filters: Ref<AdminSearchFilters>;
  sortBy: Ref<string>;
  results: Ref<unknown[]> | ComputedRef<unknown[]>;
  totalHits: Ref<number> | ComputedRef<number>;
  error: Ref<AdminSearchError | null> | Ref<null>;
  facets: Ref<AdminFacet[]> | ComputedRef<AdminFacet[]>;
  isSearching: Ref<boolean> | ComputedRef<boolean>;
  isLoadingFacets: Ref<boolean> | ComputedRef<boolean>;
  isLoadingMore: Ref<boolean> | ComputedRef<boolean>;
  hasMoreResults: Ref<boolean> | ComputedRef<boolean>;
  hasActiveFilters: Ref<boolean> | ComputedRef<boolean>;
  activeFilterCount: Ref<number> | ComputedRef<number>;
  search: (query: string, immediate?: boolean) => void | Promise<void>;
  setFilter: (field: string, value: unknown) => void;
  toggleFilter: (field: string, value: string | number) => void;
  clearFilters: () => void;
  loadMore: () => void | Promise<void>;
  setSortBy: (sortBy: string) => void;
}
