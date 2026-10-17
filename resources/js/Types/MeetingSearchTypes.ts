// Meeting search interfaces - adapted from DocumentSearchTypes

export type MeetingSearchSort = 'relevance' | 'date_desc' | 'date_asc';

// Core search interfaces
export interface MeetingSearchFilters {
  query: string;
  tenants: string[];
  institutionTypes: string[];
  years: number[];
  successRateRanges: string[]; // 'high', 'medium', 'low'
  dateRange: {
    from?: Date;
    to?: Date;
    preset?: 'recent' | '1year' | 'year-range' | 'custom';
  };
  /** Optional so existing partial-filter object literals elsewhere keep type-checking. */
  sort?: MeetingSearchSort;
}

export interface MeetingFacet {
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

// Meeting-specific preferences
export interface MeetingSearchPreferences {
  viewMode: 'list' | 'compact';
  recentSearches: string[];
}

// Meeting search result processing
export interface ProcessedMeetingSearchResult {
  hits: any[];
  totalHits: number;
  facets: MeetingFacet[];
  currentPage: number;
  totalPages: number;
}

// Composable return type
export interface MeetingSearchController {
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
  setFilter: <K extends keyof MeetingSearchFilters>(key: K, value: MeetingSearchFilters[K]) => void;
  toggleTenant: (tenantShortname: string) => void;
  toggleInstitutionType: (type: string) => void;
  toggleYear: (year: number) => void;
  toggleSuccessRate: (range: string) => void;
  setDateRange: (dateRange: any) => void;
  setSortBy: (sort: MeetingSearchSort) => void;
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
