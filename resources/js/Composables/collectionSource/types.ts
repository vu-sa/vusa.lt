import type { ComputedRef, Ref } from 'vue';

export interface CollectionFacetValue {
  value: string;
  label: string;
  /** Undefined when the source has not counted it (yet); the panel then shows no number. */
  count?: number | null;
  isSelected: boolean;
}

export interface CollectionFacet {
  remote?: boolean;
  field: string;
  label: string;
  type: 'checkbox' | 'year-pills';
  values: CollectionFacetValue[];
}

export interface CollectionSortOption {
  value: string;
  label: string;
}

/** One removable "this is filtering the list" chip. */
export interface CollectionChip {
  id: string;
  label: string;
}

/**
 * What `CollectionPage` needs from wherever the rows come from, so it never learns where they
 * live. Three implementations: Typesense (big, searchable collections), the admin API
 * (workflow collections with server-side facets) and local (small lists sent as a prop).
 */
export interface CollectionSource<T> {
  items: Readonly<Ref<readonly T[]>>;
  total: Readonly<Ref<number>>;
  isLoading: Readonly<Ref<boolean>>;
  isLoadingMore: Readonly<Ref<boolean>>;
  hasMore: Readonly<Ref<boolean>>;
  /** False until the first request has come back, so the first paint shows a skeleton, not "empty". */
  hasSearched: Readonly<Ref<boolean>>;
  error: Readonly<Ref<string | null>>;
  query: Readonly<Ref<string>>;
  filters: Readonly<Ref<Record<string, unknown>>>;
  facets: ComputedRef<CollectionFacet[]>;
  chips: ComputedRef<CollectionChip[]>;
  /** Filters in force, not counting the text query. */
  activeFilterCount: Readonly<Ref<number>>;
  sortBy: Readonly<Ref<string>>;
  sortOptions: ComputedRef<CollectionSortOption[]>;
  search: (query: string, immediate?: boolean) => void;
  toggleFilter: (field: string, value: string) => void;
  setFilter: (field: string, value: unknown) => void;
  clearFilters: () => void;
  clearChip: (id: string) => void;
  /** Local-only replacement for optimistic mutations. Search adapters may safely ignore it. */
  replaceItems: (items: T[]) => void;
  /**
   * Overlay a change on loaded rows (matched by `id`) until the source catches up — a queued
   * Scout sync can lag a save by seconds. Returns an undo for a request that fails.
   */
  patchItems: (ids: string[], patch: Partial<T>) => () => void;
  /** Leave rows out of the list (just deleted); returns an undo. */
  hideItems: (ids: string[]) => () => void;
  setSortBy: (value: string) => void;
  loadMore: () => void;
  refresh: () => void;
  /** Counting costs a query per value, so a source may wait until the filter panel is opened. */
  loadFacets?: () => void;
}

export interface TypesenseCollectionSource<T> extends CollectionSource<T> {
  /** Institutions where the user has a duty of their own, per the scoped search key. */
  directInstitutionIds: ComputedRef<string[]>;
  /**
   * What the user reaches beyond public rows, per the scoped key: their padaliniai (the
   * collection's `.padalinys` permission) and own/related institutions (its `.own` permission).
   */
  accessScope: ComputedRef<{ isSuperAdmin: boolean; tenantIds: number[]; institutionIds: string[] }>;
}
