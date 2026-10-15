import type { ComputedRef } from 'vue';

export type DocumentSearchSort = 'relevance' | 'date_desc' | 'date_asc';

/**
 * The three states `calculateIsInEffect()` (app/Models/Document.php) can produce:
 * `true`/`false` when the document has effective/expiration dates, `unknown` when it has
 * neither (indexed as an absent optional field, not a literal null).
 */
export type DocumentEffectStatusValue = 'true' | 'false' | 'unknown';

// Core search interfaces
export interface DocumentSearchFilters {
  query: string;
  tenants: string[];
  contentTypes: string[];
  languages: string[];
  dateRange: {
    from?: Date;
    to?: Date;
    preset?: 'recent' | '1year' | 'year-range' | 'custom';
  };
  /** Optional so existing partial-filter object literals elsewhere keep type-checking. */
  sort?: DocumentSearchSort;
  /** Defaults to `['true', 'unknown']` — hides "Negalioja" until explicitly selected. */
  effectStatuses?: DocumentEffectStatusValue[];
}

export interface DocumentFacet {
  field: string;
  label: string;
  values: Array<{
    value: string;
    label: string;
    count: number | null;
    highlighted?: string;
    isSelected?: boolean;
    level?: number; // For hierarchical display (e.g., tenant hierarchy)
  }>;
}

// Search error handling
export interface SearchError {
  type: 'network' | 'server' | 'client' | 'timeout' | 'abort';
  message: string;
  userMessage: string; // User-friendly message
  code?: string | number;
  retryable: boolean;
  timestamp: Date;
}

// Search status and state
export type SearchStatus = 'idle' | 'searching' | 'loading-more' | 'loading-facets' | 'error';

export interface SearchState {
  status: SearchStatus;
  hasResults: boolean;
  totalHits: number;
  query: string;
  filters: DocumentSearchFilters;
  facets: DocumentFacet[];
  results: any[];
  viewMode: 'list' | 'compact';
  error: SearchError | null;
  isOnline: boolean;
}

// User preferences
export interface DocumentSearchPreferences {
  viewMode: 'list' | 'compact';
  recentSearches: string[];
}

// Composable return types (for better TypeScript support)
export interface DocumentSearchController {
  // State
  searchState: any;
  isSearching: any;
  isLoadingFacets: any;
  isLoadingMore: any;
  hasResults: any;
  hasActiveFilters: any;
  hasMoreResults: any;
  totalHits: any;
  results: any;
  facets: any;
  filters: any;
  hasQuery: ComputedRef<boolean>;
  activeSort: ComputedRef<DocumentSearchSort>;
  viewMode: any;
  recentSearches: any;

  // Error handling
  searchError: any;
  isOnline: any;
  retryCount: any;
  maxRetries: number;

  // Actions
  search: (query: string, immediate?: boolean) => void;
  setFilter: <K extends keyof DocumentSearchFilters>(key: K, value: DocumentSearchFilters[K]) => void;
  toggleTenant: (tenantShortname: string) => void;
  toggleContentType: (contentType: string) => void;
  toggleLanguage: (language: string) => void;
  setDateRange: (dateRange: any) => void;
  setSortBy: (sort: DocumentSearchSort) => void;
  toggleEffectStatus: (value: DocumentEffectStatusValue) => void;
  clearEffectStatuses: () => void;
  setViewMode: (mode: 'list' | 'compact') => void;
  clearFilters: () => void;
  clearRecentSearches: () => void;
  removeRecentSearch: (search: string) => void;
  loadMore: () => void;
  retrySearch: () => void;
  clearError: () => void;
  loadInitialFacets: () => Promise<void>;

  // Internal
  searchClient: any;
  initializeSearchClient: () => any;
  cancelPendingSearch?: () => void;
}
