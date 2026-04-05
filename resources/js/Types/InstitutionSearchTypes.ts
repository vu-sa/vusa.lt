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

// Institution-specific preferences
export interface InstitutionSearchPreferences {
  viewMode: 'grid' | 'list';
  recentSearches: string[];
}

// Institution search result - matches Typesense indexed data
export interface InstitutionSearchResult {
  id: string;
  name_lt: string;
  name_en: string;
  short_name_lt: string | null;
  short_name_en: string | null;
  alias: string;
  email: string | null;
  phone: string | null;
  website: string | null;
  address_lt: string | null;
  address_en: string | null;
  image_url: string | null;
  logo_url: string | null;
  facebook_url: string | null;
  instagram_url: string | null;
  tenant_id: number | null;
  tenant_shortname: string | null;
  tenant_alias: string | null;
  type_ids: number[];
  type_slugs: string[];
  type_titles_lt: string[];
  type_titles_en: string[];
  duties_count: number;
  has_contacts: boolean;
  created_at: number;
  updated_at: number;
}

// Processed search result with locale-aware fields
export interface ProcessedInstitutionResult {
  id: string;
  name: string;
  short_name: string | null;
  alias: string;
  email: string | null;
  phone: string | null;
  website: string | null;
  address: string | null;
  image_url: string | null;
  logo_url: string | null;
  facebook_url: string | null;
  instagram_url: string | null;
  tenant: {
    id: number | null;
    shortname: string | null;
    alias: string | null;
  } | null;
  types: Array<{
    id: number;
    slug: string;
    title: string;
  }>;
  duties_count: number;
  has_contacts: boolean;
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
}

// Constants
export const INSTITUTION_SEARCH_CONSTANTS = {
  MIN_QUERY_LENGTH: 2,
  DEFAULT_PER_PAGE: 24,
  MAX_RETRIES: 3,
  SEARCH_TIMEOUT: 10000,
  DEBOUNCE_DELAY: 200,
  MAX_RECENT_SEARCHES: 10,
  MAX_FACET_VALUES: 50,
} as const;

export const INSTITUTION_FACET_FIELD_LABELS: Record<string, string> = {
  tenant_shortname: 'Organization',
  type_slugs: 'Type',
  has_contacts: 'Has Contacts',
} as const;
