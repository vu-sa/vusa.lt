// Core search interfaces
export interface DocumentSearchFilters {
  query: string;
  tenants: string[];
  contentTypes: string[];
  languages: string[];
  dateRange: {
    from?: Date;
    to?: Date;
    preset?: 'recent' | '3months' | '6months' | '1year' | 'year-range' | 'custom';
  };
}

export interface DocumentFacet {
  field: string;
  label: string;
  values: Array<{
    value: string;
    label: string;
    count: number;
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

// Language utilities
export interface LanguageInfo {
  code: string;
  display: string;
  flag: string;
}

// Typesense client interfaces
export interface TypesenseNode {
  protocol: string;
  host: string;
  port: number;
}

export interface TypesenseConfig {
  apiKey: string;
  nodes: TypesenseNode[];
}

export interface SearchClient {
  search: (collection: string, searchParams: any) => Promise<any>;
  apiKey: string;
  nodes: TypesenseNode[];
}

// API response interfaces
export interface SearchResponse {
  hits?: Array<{ document: any }>;
  found?: number;
  facet_counts?: Array<{
    field_name: string;
    counts: Array<{
      value: string;
      count: number;
    }>;
  }>;
}

export interface SearchParams {
  q: string;
  query_by: string;
  facet_by: string;
  max_facet_values: number;
  per_page: number;
  page: number;
  sort_by: string;
  filter_by?: string;
}

// Component prop interfaces
export interface DocumentSearchInterfaceProps {
  initialQuery?: string;
  initialFilters?: Record<string, any>;
}

// Facet statistics
export interface FacetStats {
  totalFacets: number;
  activeFacets: number;
  totalValues: number;
  selectedValues: number;
}

// Language display helpers
export interface LanguageInfo {
  code: string;
  display: string;
  flag: string;
}

// Search result processing
export interface ProcessedSearchResult {
  hits: any[];
  totalHits: number;
  facets: DocumentFacet[];
  currentPage: number;
  totalPages: number;
}

// Error handling types
export type ErrorHandler = (error: unknown, context: string) => void;
export type RetryFunction = () => void;

// Event handler types
export type SearchEventHandler = (query: string) => void;
export type FilterEventHandler = (filterType: string, value: any) => void;
export type ViewModeEventHandler = (mode: 'list' | 'compact') => void;

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

// Utility type for making properties optional
export type PartialBy<T, K extends keyof T> = Omit<T, K> & Partial<Pick<T, K>>;

// Type guards
export const isSearchError = (error: any): error is SearchError => {
  return error && typeof error === 'object' && 'type' in error && 'message' in error;
};

export const isValidSearchStatus = (status: string): status is SearchStatus => {
  return ['idle', 'searching', 'loading-more', 'loading-facets', 'error'].includes(status);
};

// Constants
export const SEARCH_CONSTANTS = {
  MIN_QUERY_LENGTH: 3,
  DEFAULT_PER_PAGE: 24,
  MAX_RETRIES: 3,
  SEARCH_TIMEOUT: 10000,
  DEBOUNCE_DELAY: 300,
  MAX_RECENT_SEARCHES: 10,
  MAX_FACET_VALUES: 50,
} as const;

export const FACET_FIELD_LABELS: Record<string, string> = {
  content_type: 'Document Type',
  tenant_shortname: 'Organization',
  language: 'Language',
  document_date: 'Date',
} as const;

export const DATE_RANGE_PRESETS = {
  'recent': 'Recent (3 months)',
  '3months': 'Last 3 months',
  '6months': 'Last 6 months',
  '1year': 'Last year',
  'year-range': 'Year range',
  'custom': 'Custom range',
} as const;
