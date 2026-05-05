// Meeting search interfaces - adapted from DocumentSearchTypes

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
    preset?: 'recent' | '3months' | '6months' | '1year' | 'year-range' | 'custom';
  };
}

export interface MeetingFacet {
  field: string;
  label: string;
  values: Array<{
    value: string;
    label: string;
    count: number;
    highlighted?: string;
    isSelected?: boolean;
    level?: number;
  }>;
}

// Re-use search error, status, and state types from document search
export type {
  SearchError,
  SearchStatus,
  SearchState,
} from './DocumentSearchTypes';

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
}

// Constants
export const MEETING_SEARCH_CONSTANTS = {
  MIN_QUERY_LENGTH: 3,
  DEFAULT_PER_PAGE: 24,
  MAX_RETRIES: 3,
  SEARCH_TIMEOUT: 10000,
  DEBOUNCE_DELAY: 300,
  MAX_RECENT_SEARCHES: 10,
  MAX_FACET_VALUES: 50,
} as const;

export const MEETING_FACET_FIELD_LABELS: Record<string, string> = {
  tenant_shortname: 'Organization',
  institution_type_title: 'Institution Type',
  year: 'Year',
  month: 'Month',
  student_success_rate: 'Success Rate',
} as const;

export const SUCCESS_RATE_RANGES: Record<string, { label: string; min?: number; max?: number }> = {
  high: { label: 'High (>75%)', min: 75 },
  medium: { label: 'Medium (50-75%)', min: 50, max: 74 },
  low: { label: 'Low (<50%)', max: 49 },
} as const;
