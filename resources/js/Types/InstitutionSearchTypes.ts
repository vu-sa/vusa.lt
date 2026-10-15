// Institution search interfaces - adapted from DocumentSearchTypes

// Core search interfaces
export interface InstitutionSearchFilters {
  query: string;
  tenants: string[];
  types: string[]; // Institution type slugs for filtering
  hasContacts: boolean | null; // Filter by whether institution has active contacts
}

export interface InstitutionFacet {
  field: string;
  label: string;
  values: Array<{
    value: string;
    label: string;
    count: number | null;
    highlighted?: string;
    isSelected?: boolean;
    level?: number;
  }>;
}

export type { SearchError } from './DocumentSearchTypes';

// Institution-specific preferences
export interface InstitutionSearchPreferences {
  viewMode: 'grid' | 'list';
  recentSearches: string[];
}

// Composable return type
export interface InstitutionSearchController {
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
  viewMode: any;
  recentSearches: any;

  // Error handling
  searchError: any;
  isOnline: any;
  retryCount: any;
  maxRetries: number;

  // Actions
  search: (query: string, immediate?: boolean) => void;
  setFilter: <K extends keyof InstitutionSearchFilters>(key: K, value: InstitutionSearchFilters[K]) => void;
  toggleTenant: (tenantShortname: string) => void;
  toggleType: (typeSlug: string) => void;
  setHasContacts: (value: boolean | null) => void;
  setViewMode: (mode: 'grid' | 'list') => void;
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
