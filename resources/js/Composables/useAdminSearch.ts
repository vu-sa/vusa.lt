import { ref, computed, onMounted } from 'vue';

import { buildInfix } from '@/Features/Admin/AdminSearch/Utils/searchParams';
import { ErrorUtils } from '@/Shared/Search/services/SearchErrorUtils';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';
import type {
  TypesenseNode,
  AdminSearchConfig,
  ScopedCollectionConfig,
  MeetingSearchResult,
  AgendaItemSearchResult,
  NewsSearchResult,
  PageSearchResult,
  CalendarSearchResult,
  InstitutionSearchResult,
  DocumentSearchResult,
  ResourceSearchResult,
  DutySearchResult,
  MultiSearchResults,
} from '@/Shared/Search/types';

// Re-export types for backward compatibility
export type {
  MeetingSearchResult,
  AgendaItemSearchResult,
  NewsSearchResult,
  PageSearchResult,
  CalendarSearchResult,
  InstitutionSearchResult,
  DocumentSearchResult,
  ResourceSearchResult,
  DutySearchResult,
  MultiSearchResults,
} from '@/Shared/Search/types';

/**
 * Admin Search Types
 */
interface AdminSearchResult {
  id: string;
  title?: string;
  description?: string;
  start_time?: number;
  tenant_id?: number;
  tenant_name?: string;
  [key: string]: any;
}

interface SearchState {
  results: AdminSearchResult[];
  isLoading: boolean;
  error: string | null;
  totalHits: number;
  query: string;
}

/**
 * Composable for admin search with scoped Typesense API keys.
 *
 * This composable:
 * 1. Fetches a scoped API key from the backend (with tenant filtering embedded)
 * 2. Uses that key directly with Typesense for searches
 * 3. Handles key expiration and refresh
 *
 * The scoped key enforces authorization at the Typesense level - the key itself
 * contains filter_by constraints that cannot be bypassed by the client.
 */
export const useAdminSearch = () => {
  // Config state
  const config = ref<AdminSearchConfig | null>(null);
  const isConfigLoading = ref(false);
  const configError = ref<string | null>(null);

  // Request guards to prevent duplicate/rapid requests
  let configFetchPromise: Promise<void> | null = null;
  let searchAbortController: AbortController | null = null;
  const rateLimitedUntil = ref<number>(0);

  // Search state
  const searchState = ref<SearchState>({
    results: [],
    isLoading: false,
    error: null,
    totalHits: 0,
    query: '',
  });

  // Computed
  const isReady = computed(() => config.value !== null && !configError.value);
  const hasExpired = computed(() => {
    if (!config.value?.expiresAt) return false;
    return Date.now() / 1000 > config.value.expiresAt - 60; // 1 minute buffer
  });

  /**
   * Get the list of collections the user has access to
   */
  const accessibleCollections = computed((): string[] => {
    if (!config.value?.collections) return [];
    return Object.entries(config.value.collections)
      .filter(([, collectionConfig]) => collectionConfig.hasAccess)
      .map(([key]) => key);
  });

  /**
   * Check if user has any search access at all
   */
  const hasAnyAccess = computed(() => accessibleCollections.value.length > 0);

  /**
   * Check if we're currently rate limited
   */
  const isRateLimited = computed(() => Date.now() < rateLimitedUntil.value);

  /**
   * Fetch scoped config from backend (with deduplication)
   */
  const fetchConfig = async (): Promise<void> => {
    // If already fetching, wait for that promise
    if (configFetchPromise) {
      return configFetchPromise;
    }

    // If we already have a valid config, skip
    if (config.value && !hasExpired.value) {
      return;
    }

    // Check rate limit
    if (isRateLimited.value) {
      configError.value = 'Too many requests. Please wait a moment.';
      return;
    }

    isConfigLoading.value = true;
    configError.value = null;

    configFetchPromise = (async () => {
      try {
        const response = await fetch('/api/v1/admin/search/config', {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        if (!response.ok) {
          if (response.status === 401) {
            configError.value = 'Unauthorized - please log in';
          }
          else if (response.status === 429) {
            const retryAfter = parseInt(response.headers.get('retry-after') || '5', 10);
            rateLimitedUntil.value = Date.now() + retryAfter * 1000;
            configError.value = 'Too many requests. Please wait a moment.';
          }
          else if (response.status === 503) {
            configError.value = 'Typesense search is not configured';
          }
          else {
            configError.value = `Failed to load search configuration (HTTP ${response.status})`;
          }
          return;
        }

        config.value = await response.json();
      }
      catch (error: unknown) {
        const message = error instanceof Error ? error.message : 'Failed to load search configuration';
        configError.value = message;
      }
      finally {
        isConfigLoading.value = false;
        configFetchPromise = null;
      }
    })();

    return configFetchPromise;
  };

  /**
   * Refresh the scoped key (with deduplication)
   */
  const refreshConfig = async (): Promise<void> => {
    // If already fetching config, wait for that
    if (configFetchPromise) {
      return configFetchPromise;
    }

    // Check rate limit
    if (isRateLimited.value) {
      configError.value = 'Too many requests. Please wait a moment.';
      return;
    }

    isConfigLoading.value = true;
    configError.value = null;

    configFetchPromise = (async () => {
      try {
        const response = await fetch('/api/v1/admin/search/refresh-key', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        if (!response.ok) {
          if (response.status === 429) {
            const retryAfter = parseInt(response.headers.get('retry-after') || '5', 10);
            rateLimitedUntil.value = Date.now() + retryAfter * 1000;
            configError.value = 'Too many requests. Please wait a moment.';
          }
          else {
            configError.value = `Failed to refresh search key (HTTP ${response.status})`;
          }
          return;
        }

        const data = await response.json();
        if (data.success && data.config) {
          config.value = data.config;
        }
      }
      catch (error: unknown) {
        const message = error instanceof Error ? error.message : 'Failed to refresh search key';
        configError.value = message;
      }
      finally {
        isConfigLoading.value = false;
        configFetchPromise = null;
      }
    })();

    return configFetchPromise;
  };

  /**
   * Get the API key for a specific collection
   */
  const getCollectionApiKey = (collection: string): string | null => {
    if (!config.value?.collections) return null;

    // Try exact match first (e.g., 'meetings')
    if (config.value.collections[collection]) {
      const { key } = config.value.collections[collection];

      return key && key.trim().length > 0 ? key : null;
    }

    // Try without prefix (collection name might include prefix from Typesense)
    for (const [key, collectionConfig] of Object.entries(config.value.collections)) {
      if (collectionConfig.name === collection) {
        const apiKey = collectionConfig.key;

        return apiKey && apiKey.trim().length > 0 ? apiKey : null;
      }
    }

    return null;
  };

  /**
   * Check if user has access to a specific collection
   */
  const hasCollectionAccess = (collection: string): boolean => {
    if (!config.value?.collections) return false;

    const collectionConfig = config.value.collections[collection];
    return collectionConfig?.hasAccess ?? false;
  };

  /**
   * Tenant ids the current user can access for a collection. Empty for
   * super-admins (unrestricted) or when config has not loaded yet.
   */
  const getCollectionTenantIds = (collection: string): number[] => {
    return config.value?.collections?.[collection]?.tenantIds ?? [];
  };

  const isSuperAdmin = computed(() => config.value?.isSuperAdmin ?? false);

  /**
   * Perform a search against a collection
   */
  const search = async (
    collection: string,
    query: string,
    options: {
      queryBy?: string;
      filterBy?: string;
      sortBy?: string;
      perPage?: number;
      page?: number;
      facetBy?: string;
    } = {},
  ): Promise<void> => {
    // Ensure we have a valid config
    if (!config.value) {
      await fetchConfig();
    }

    // Check for expiration
    if (hasExpired.value) {
      await refreshConfig();
    }

    if (!config.value) {
      searchState.value.error = configError.value || 'No search configuration available';
      return;
    }

    // Get the API key for this specific collection
    const apiKey = getCollectionApiKey(collection);
    if (!apiKey) {
      searchState.value.error = `No API key available for collection: ${collection}`;
      return;
    }

    searchState.value.isLoading = true;
    searchState.value.error = null;
    searchState.value.query = query;

    try {
      const node = config.value.nodes?.[0];
      if (!node) {
        throw new Error('No Typesense nodes configured');
      }

      const baseUrl = `${node.protocol}://${node.host}:${node.port}`;

      // Get the full collection name (with prefix)
      const collectionName = config.value.collections[collection]?.name || collection;

      const searchParams = new URLSearchParams({
        q: query || '*',
        query_by: options.queryBy || 'title',
        per_page: String(options.perPage || 20),
        page: String(options.page || 1),
      });

      if (options.filterBy) {
        searchParams.append('filter_by', options.filterBy);
      }

      if (options.sortBy) {
        searchParams.append('sort_by', options.sortBy);
      }

      if (options.facetBy) {
        searchParams.append('facet_by', options.facetBy);
      }

      const response = await fetch(
        `${baseUrl}/collections/${collectionName}/documents/search?${searchParams}`,
        {
          method: 'GET',
          headers: {
            'X-TYPESENSE-API-KEY': apiKey,
            'Content-Type': 'application/json',
          },
        },
      );

      if (!response.ok) {
        // Handle 401 - expired or invalid key
        if (response.status === 401) {
          await refreshConfig();
          // Retry the search once
          if (config.value) {
            return search(collection, query, options);
          }
        }

        const errorText = await response.text();
        throw new Error(`Typesense error: ${response.status} - ${errorText}`);
      }

      const data = await response.json();

      searchState.value.results = data.hits?.map((hit: any) => hit.document) || [];
      searchState.value.totalHits = data.found || 0;
    }
    catch (error: unknown) {
      // Use ErrorUtils for consistent error handling
      if (ErrorUtils.isAbortError(error)) {
        return; // Don't update state for aborted requests
      }
      const searchError = ErrorUtils.fromError(error, 'search');
      searchState.value.error = searchError.userMessage;
      searchState.value.results = [];
      searchState.value.totalHits = 0;
    }
    finally {
      searchState.value.isLoading = false;
    }
  };

  /**
   * Search meetings collection
   */
  const searchMeetings = async (query: string, options: {
    perPage?: number;
    page?: number;
    filterBy?: string;
  } = {}): Promise<void> => {
    return search('meetings', query, {
      // Use only core fields that are guaranteed to exist
      queryBy: 'title,description,institution_name_lt,institution_name_en',
      sortBy: 'start_time:desc',
      ...options,
    });
  };

  /**
   * Search agenda items collection
   */
  const searchAgendaItems = async (query: string, options: {
    perPage?: number;
    page?: number;
    filterBy?: string;
  } = {}): Promise<void> => {
    return search('agenda_items', query, {
      queryBy: 'title,description,student_benefit,meeting_title',
      sortBy: 'meeting_start_time:desc',
      ...options,
    });
  };

  /**
   * Initialize the search - fetch config on mount
   */
  const initialize = async (): Promise<void> => {
    await fetchConfig();
  };

  /**
   * Perform a multi-search across all admin collections
   * Uses Typesense's multi_search endpoint with per-search embedded API keys
   * for proper per-collection authorization in a single request.
   *
   * Only searches collections the user has access to. Returns empty results
   * for inaccessible collections without throwing errors.
   */
  const multiSearch = async (
    query: string,
    options: {
      meetingsLimit?: number;
      agendaItemsLimit?: number;
      newsLimit?: number;
      pagesLimit?: number;
      calendarLimit?: number;
      institutionsLimit?: number;
      documentsLimit?: number;
      resourcesLimit?: number;
      dutiesLimit?: number;
      usersLimit?: number;
      _retryCount?: number; // Internal retry counter
    } = {},
  ): Promise<MultiSearchResults> => {
    const {
      meetingsLimit = 5,
      agendaItemsLimit = 5,
      newsLimit = 5,
      pagesLimit = 5,
      calendarLimit = 5,
      institutionsLimit = 5,
      documentsLimit = 5,
      resourcesLimit = 5,
      dutiesLimit = 5,
      usersLimit = 5,
      _retryCount = 0,
    } = options;

    const emptyResults = (): MultiSearchResults => createEmptyMultiSearchResults();

    // Check rate limit before doing anything
    if (isRateLimited.value) {
      throw new Error('Too many requests. Please wait a moment.');
    }

    // Cancel any in-flight search
    if (searchAbortController) {
      searchAbortController.abort();
    }
    searchAbortController = new AbortController();
    const { signal } = searchAbortController;

    // Ensure we have a valid config
    if (!config.value) {
      await fetchConfig();
    }

    // Check for expiration
    if (hasExpired.value) {
      await refreshConfig();
    }

    if (!config.value) {
      throw new Error(configError.value || 'No search configuration available');
    }

    // If user has no access to any collections, return empty results immediately
    if (!hasAnyAccess.value) {
      return emptyResults();
    }

    const node = config.value.nodes?.[0];
    if (!node) {
      throw new Error('No Typesense nodes configured');
    }

    const baseUrl = `${node.protocol}://${node.host}:${node.port}`;

    // Collection configs
    const meetingsConfig = config.value.collections['meetings'];
    const agendaItemsConfig = config.value.collections['agenda_items'];
    const newsConfig = config.value.collections['news'];
    const pagesConfig = config.value.collections['pages'];
    const calendarConfig = config.value.collections['calendar'];
    const institutionsConfig = config.value.collections['institutions'];
    const documentsConfig = config.value.collections['documents'];
    const resourcesConfig = config.value.collections['resources'];
    const dutiesConfig = config.value.collections['duties'];
    const usersConfig = config.value.collections['users'];

    // Build searches array with per-search API keys embedded
    // This allows different scoped keys per collection in a single multi_search request
    // Only include collections the user has access to
    const searches: Array<Record<string, unknown>> = [];
    const searchOrder: string[] = []; // Track which collection each search corresponds to

    // When a query is present, rank by Typesense relevance (text_match) with the
    // collection's date field as a recency tiebreak; with no query, browse by date.
    const hasQuery = !!query && query.trim() !== '';
    const relevanceSort = (dateField: string, direction: 'asc' | 'desc' = 'desc'): string =>
      hasQuery
        ? `_text_match(buckets:10):desc,${dateField}:${direction}`
        : `${dateField}:${direction}`;

    // Relevance tuning shared by every sub-search (mirrors config/scout.php
    // typesense.model-settings, which the multi_search call does not otherwise apply).
    const commonTuning = {
      prioritize_exact_match: true,
      prioritize_token_position: true,
      num_typos: 2,
      typo_tokens_threshold: 1,
    } as const;

    if (meetingsConfig?.hasAccess && meetingsConfig?.key) {
      searches.push({
        'collection': meetingsConfig.name,
        'q': query || '*',
        'query_by': 'title,description,institution_name_lt,institution_name_en',
        'query_by_weights': '10,5,4,4',
        'infix': buildInfix('title,description,institution_name_lt,institution_name_en', 'meetings'),
        'sort_by': relevanceSort('start_time'),
        ...commonTuning,
        'per_page': meetingsLimit,
        'x-typesense-api-key': meetingsConfig.key,
      });
      searchOrder.push('meetings');
    }

    if (agendaItemsConfig?.hasAccess && agendaItemsConfig?.key) {
      searches.push({
        'collection': agendaItemsConfig.name,
        'q': query || '*',
        'query_by': 'title,description,student_benefit,meeting_title',
        'query_by_weights': '10,4,3,6',
        'infix': buildInfix('title,description,student_benefit,meeting_title', 'agenda_items'),
        'sort_by': relevanceSort('meeting_start_time'),
        ...commonTuning,
        'per_page': agendaItemsLimit,
        'x-typesense-api-key': agendaItemsConfig.key,
      });
      searchOrder.push('agendaItems');
    }

    if (newsConfig?.hasAccess && newsConfig?.key) {
      searches.push({
        'collection': newsConfig.name,
        'q': query || '*',
        'query_by': 'title,short',
        'query_by_weights': '10,5',
        'infix': buildInfix('title,short', 'news'),
        'sort_by': relevanceSort('publish_time'),
        ...commonTuning,
        'per_page': newsLimit,
        'x-typesense-api-key': newsConfig.key,
      });
      searchOrder.push('news');
    }

    if (pagesConfig?.hasAccess && pagesConfig?.key) {
      searches.push({
        'collection': pagesConfig.name,
        'q': query || '*',
        'query_by': 'title,meta_description',
        'query_by_weights': '10,4',
        'infix': buildInfix('title,meta_description', 'pages'),
        'sort_by': relevanceSort('created_at'),
        ...commonTuning,
        'per_page': pagesLimit,
        'x-typesense-api-key': pagesConfig.key,
      });
      searchOrder.push('pages');
    }

    if (calendarConfig?.hasAccess && calendarConfig?.key) {
      searches.push({
        'collection': calendarConfig.name,
        'q': query || '*',
        'query_by': 'title,title_lt,title_en',
        'query_by_weights': '10,8,8',
        'infix': buildInfix('title,title_lt,title_en', 'calendar'),
        'sort_by': relevanceSort('date'),
        ...commonTuning,
        'per_page': calendarLimit,
        'x-typesense-api-key': calendarConfig.key,
      });
      searchOrder.push('calendar');
    }

    if (institutionsConfig?.hasAccess && institutionsConfig?.key) {
      searches.push({
        'collection': institutionsConfig.name,
        'q': query || '*',
        'query_by': 'name_lt,name_en,short_name_lt,short_name_en,alias,email',
        'query_by_weights': '10,8,6,4,3,2',
        'infix': buildInfix('name_lt,name_en,short_name_lt,short_name_en,alias,email', 'institutions'),
        'sort_by': relevanceSort('created_at'),
        ...commonTuning,
        'per_page': institutionsLimit,
        'x-typesense-api-key': institutionsConfig.key,
      });
      searchOrder.push('institutions');
    }

    if (documentsConfig?.hasAccess && documentsConfig?.key) {
      searches.push({
        'collection': documentsConfig.name,
        'q': query || '*',
        'query_by': 'title,summary,content_type,document_year',
        'query_by_weights': '10,5,3,2',
        'infix': buildInfix('title,summary,content_type,document_year', 'documents'),
        'sort_by': relevanceSort('document_date'),
        ...commonTuning,
        'per_page': documentsLimit,
        'x-typesense-api-key': documentsConfig.key,
      });
      searchOrder.push('documents');
    }

    if (resourcesConfig?.hasAccess && resourcesConfig?.key) {
      searches.push({
        'collection': resourcesConfig.name,
        'q': query || '*',
        'query_by': 'name_lt,name_en,description_lt,description_en,location',
        'query_by_weights': '10,8,4,4,3',
        'infix': buildInfix('name_lt,name_en,description_lt,description_en,location', 'resources'),
        'sort_by': relevanceSort('created_at'),
        ...commonTuning,
        // Disable typo tolerance on the structured address field: fuzzy matching
        // there adds no value and produces noise (e.g. a personal name matching a
        // common street word one typo away). Order must match query_by.
        'num_typos': '2,2,2,2,0',
        'per_page': resourcesLimit,
        'x-typesense-api-key': resourcesConfig.key,
      });
      searchOrder.push('resources');
    }

    if (dutiesConfig?.hasAccess && dutiesConfig?.key) {
      searches.push({
        'collection': dutiesConfig.name,
        'q': query || '*',
        'query_by': 'name_lt,name_en,email,institution_name_lt,institution_name_en,current_user_names,previous_user_names',
        'query_by_weights': '10,8,4,4,3,3,2',
        'infix': buildInfix('name_lt,name_en,email,institution_name_lt,institution_name_en,current_user_names,previous_user_names', 'duties'),
        // Duties have no meaningful date; tiebreak alphabetically by name.
        'sort_by': relevanceSort('name_lt', 'asc'),
        ...commonTuning,
        'per_page': dutiesLimit,
        'x-typesense-api-key': dutiesConfig.key,
      });
      searchOrder.push('duties');
    }

    if (usersConfig?.hasAccess && usersConfig?.key) {
      searches.push({
        'collection': usersConfig.name,
        'q': query || '*',
        'query_by': 'name,email,phone,current_duty_names,previous_duty_names',
        'query_by_weights': '10,6,4,3,2',
        'sort_by': relevanceSort('created_at'),
        ...commonTuning,
        'per_page': usersLimit,
        'x-typesense-api-key': usersConfig.key,
      });
      searchOrder.push('users');
    }

    // If no collections are accessible, return empty results
    if (searches.length === 0) {
      return emptyResults();
    }

    try {
      // Use the dedicated header key for multi_search endpoint authentication
      // Individual searches use their own collection-scoped keys via x-typesense-api-key
      const headerApiKey = config.value.headerKey;
      if (!headerApiKey) {
        throw new Error('No header API key available for search');
      }

      const response = await fetch(`${baseUrl}/multi_search`, {
        method: 'POST',
        headers: {
          'X-TYPESENSE-API-KEY': headerApiKey,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ searches }),
        signal,
      });

      if (!response.ok) {
        // Handle 401 - expired or invalid key (retry once only)
        if (response.status === 401 && _retryCount < 1) {
          console.warn('Search authentication failed, refreshing keys...');
          await refreshConfig();
          // Retry once after refreshing with incremented retry count
          if (config.value) {
            return multiSearch(query, { ...options, _retryCount: _retryCount + 1 });
          }
        }

        // If we already retried, or no config, throw the error
        if (response.status === 401) {
          const errorText = await response.text();
          console.error('Search authentication error after retry:', errorText);
          throw new Error('Paieškos autentifikacija nepavyko. Pabandykite perkrauti puslapį.');
        }

        // Handle 403 - forbidden (scoped key doesn't allow this operation)
        if (response.status === 403) {
          const errorText = await response.text();
          console.error('Search forbidden:', errorText);
          throw new Error('Neturite prieigos prie paieškos. Pabandykite perkrauti puslapį.');
        }

        // Handle 429 - rate limited
        if (response.status === 429) {
          const retryAfter = parseInt(response.headers.get('retry-after') || '5', 10);
          rateLimitedUntil.value = Date.now() + retryAfter * 1000;
          throw new Error('Too many requests. Please wait a moment.');
        }

        const errorText = await response.text();
        throw new Error(`Search request failed: ${response.status} - ${errorText}`);
      }

      const data = await response.json();

      // Initialize empty results
      const results: MultiSearchResults = emptyResults();

      // Map results back to their collections based on search order
      searchOrder.forEach((collectionKey, index) => {
        const searchResult = data.results?.[index];
        if (searchResult?.error) {
          console.warn(`${collectionKey} search error:`, searchResult.error);
          return;
        }

        const key = collectionKey as keyof Omit<MultiSearchResults, 'counts'>;
        // Attach the Typesense relevance score so the "All" tab can interleave
        // results across collections by relevance (see SearchAllPanel.vue).
        // Use the string `text_match_info.score`, not the numeric `text_match`:
        // the int64 score exceeds JS's safe-integer range, so the number form
        // loses precision and collapses distinct scores into ties. The string is
        // compared with BigInt downstream.
        const hits = searchResult?.hits?.map(
          (hit: {
            document: Record<string, unknown>;
            text_match?: number;
            text_match_info?: { score?: string };
          }) => ({
            ...hit.document,
            _text_match: hit.text_match_info?.score
              ?? (hit.text_match != null ? String(hit.text_match) : '0'),
          }),
        ) || [];
        (results[key] as unknown[]) = hits;
        results.counts[key] = typeof searchResult?.found === 'number' ? searchResult.found : hits.length;
      });

      return results;
    }
    catch (error: unknown) {
      // Don't throw for aborted requests (user typed new query)
      if (ErrorUtils.isAbortError(error)) {
        return emptyResults();
      }
      // Use ErrorUtils for consistent error handling
      const searchError = ErrorUtils.fromError(error, 'multi-search');
      throw new Error(searchError.userMessage);
    }
    finally {
      searchAbortController = null;
    }
  };

  /**
   * Search a single collection with facets.
   * Returns search results along with facet counts for filtering.
   */
  const searchWithFacets = async <T>(
    collection: string,
    query: string,
    options: {
      filterBy?: string; // Additional filters from facet selection
      sortBy?: string; // Sort order
      facetBy: string; // Comma-separated facet fields
      queryBy?: string; // Fields to search (defaults by collection)
      perPage?: number;
      page?: number;
      _retryCount?: number; // Internal retry counter
    },
  ): Promise<{
    hits: T[];
    totalHits: number;
    facets: Array<{
      field_name: string;
      counts: Array<{ value: string; count: number }>;
    }>;
    page: number;
    totalPages: number;
  }> => {
    const {
      filterBy,
      sortBy,
      facetBy,
      queryBy,
      perPage = 24,
      page = 1,
      _retryCount = 0,
    } = options;

    // Check rate limit
    if (isRateLimited.value) {
      throw new Error('Too many requests. Please wait a moment.');
    }

    // Cancel any in-flight search
    if (searchAbortController) {
      searchAbortController.abort();
    }
    searchAbortController = new AbortController();
    const { signal } = searchAbortController;

    // Ensure we have a valid config
    if (!config.value) {
      await fetchConfig();
    }

    // Check for expiration
    if (hasExpired.value) {
      await refreshConfig();
    }

    if (!config.value) {
      throw new Error(configError.value || 'No search configuration available');
    }

    // Get the API key for this collection
    const apiKey = getCollectionApiKey(collection);
    if (!apiKey) {
      throw new Error(`No API key available for collection: ${collection}`);
    }

    const node = config.value.nodes?.[0];
    if (!node) {
      throw new Error('No Typesense nodes configured');
    }

    const baseUrl = `${node.protocol}://${node.host}:${node.port}`;

    // Get the full collection name (with prefix)
    const collectionName = config.value.collections[collection]?.name || collection;

    // Build search params
    const searchParams = new URLSearchParams({
      q: query || '*',
      query_by: queryBy || 'title,description',
      per_page: String(perPage),
      page: String(page),
      facet_by: facetBy,
      max_facet_values: '50',
    });

    if (filterBy) {
      searchParams.append('filter_by', filterBy);
    }

    if (sortBy) {
      searchParams.append('sort_by', sortBy);
    }

    // Partial in-word matching as a fallback when an exact search returns nothing.
    const infix = buildInfix(queryBy || 'title,description', collection);
    if (infix) {
      searchParams.append('infix', infix);
    }

    try {
      const response = await fetch(
        `${baseUrl}/collections/${collectionName}/documents/search?${searchParams}`,
        {
          method: 'GET',
          headers: {
            'X-TYPESENSE-API-KEY': apiKey,
            'Content-Type': 'application/json',
          },
          signal,
        },
      );

      if (!response.ok) {
        // Handle 401 - expired or invalid key (retry once)
        if (response.status === 401 && _retryCount < 1) {
          console.warn('Search authentication failed, refreshing keys...');
          await refreshConfig();
          if (config.value) {
            return searchWithFacets(collection, query, { ...options, _retryCount: _retryCount + 1 });
          }
        }

        if (response.status === 401) {
          throw new Error('Search authentication failed. Please reload the page.');
        }

        if (response.status === 429) {
          const retryAfter = parseInt(response.headers.get('retry-after') || '5', 10);
          rateLimitedUntil.value = Date.now() + retryAfter * 1000;
          throw new Error('Too many requests. Please wait a moment.');
        }

        const errorText = await response.text();
        throw new Error(`Search failed: ${response.status} - ${errorText}`);
      }

      const data = await response.json();

      return {
        hits: (data.hits?.map((hit: { document: T }) => hit.document) || []) as T[],
        totalHits: data.found || 0,
        facets: data.facet_counts || [],
        page: data.page || page,
        totalPages: Math.ceil((data.found || 0) / perPage),
      };
    }
    catch (error: unknown) {
      // Don't throw for aborted requests
      if (ErrorUtils.isAbortError(error)) {
        return { hits: [], totalHits: 0, facets: [], page: 1, totalPages: 0 };
      }
      // Re-throw with consistent error handling
      const searchError = ErrorUtils.fromError(error, 'faceted-search');
      throw new Error(searchError.userMessage);
    }
    finally {
      searchAbortController = null;
    }
  };

  /**
   * Load all facet values for a collection.
   * Uses q='*' and per_page=0 to get facet counts without results.
   * Useful for populating initial filter options.
   */
  const loadInitialFacets = async (
    collection: string,
    facetBy: string,
    options: {
      filterBy?: string; // Optional base filter (e.g., for tenant scoping)
      queryBy?: string; // Fields to search (defaults to id)
      _retryCount?: number; // Internal retry counter
    } = {},
  ): Promise<Array<{
    field_name: string;
    counts: Array<{ value: string; count: number }>;
  }>> => {
    const { _retryCount = 0 } = options;
    // Check rate limit
    if (isRateLimited.value) {
      throw new Error('Too many requests. Please wait a moment.');
    }

    // Ensure we have a valid config
    if (!config.value) {
      await fetchConfig();
    }

    if (hasExpired.value) {
      await refreshConfig();
    }

    if (!config.value) {
      throw new Error(configError.value || 'No search configuration available');
    }

    // Get the API key for this collection
    const apiKey = getCollectionApiKey(collection);
    if (!apiKey) {
      throw new Error(`No API key available for collection: ${collection}`);
    }

    const node = config.value.nodes?.[0];
    if (!node) {
      throw new Error('No Typesense nodes configured');
    }

    const baseUrl = `${node.protocol}://${node.host}:${node.port}`;
    const collectionName = config.value.collections[collection]?.name || collection;

    // Build params for facet-only query
    const searchParams = new URLSearchParams({
      q: '*',
      query_by: options.queryBy || 'id', // Minimal query_by, we only need facets
      per_page: '0', // Don't need actual results
      facet_by: facetBy,
      max_facet_values: '100',
    });

    if (options.filterBy) {
      searchParams.append('filter_by', options.filterBy);
    }

    try {
      const response = await fetch(
        `${baseUrl}/collections/${collectionName}/documents/search?${searchParams}`,
        {
          method: 'GET',
          headers: {
            'X-TYPESENSE-API-KEY': apiKey,
            'Content-Type': 'application/json',
          },
        },
      );

      if (!response.ok) {
        if (response.status === 401 && _retryCount < 1) {
          await refreshConfig();
          const refreshedKey = getCollectionApiKey(collection);
          if (config.value && refreshedKey) {
            return loadInitialFacets(collection, facetBy, { ...options, _retryCount: _retryCount + 1 });
          }
        }

        const errorText = await response.text();
        throw new Error(`Failed to load facets: ${response.status} - ${errorText}`);
      }

      const data = await response.json();
      return data.facet_counts || [];
    }
    catch (error) {
      console.error('Failed to load initial facets:', error);
      return [];
    }
  };

  /**
   * Check if a result is from a related institution (not a direct duty institution).
   *
   * This helps the UI show visual indicators for results that come from institutions
   * the user can access via relationships or coordinator access, rather than direct duties.
   *
   * @param collection The collection the result is from
   * @param institutionIds The institution_ids array from the search result document
   * @returns true if the result is from a related (non-direct) institution
   */
  const isFromRelatedInstitution = (collection: string, institutionIds?: string[]): boolean => {
    if (!config.value?.collections?.[collection]) return false;
    if (!institutionIds || institutionIds.length === 0) return false;

    const collectionConfig = config.value.collections[collection];

    // For 'own' and 'combined' scopes, we filter by institutions and can detect "related"
    if (collectionConfig.scope !== 'own' && collectionConfig.scope !== 'combined') return false;

    const directIds = collectionConfig.directInstitutionIds || [];

    // If no direct IDs are available, we can't determine relatedness
    if (directIds.length === 0) return false;

    // If any of the result's institutions are in the direct list, it's not "related"
    const hasDirectMatch = institutionIds.some(id => directIds.includes(id));

    return !hasDirectMatch;
  };

  /**
   * Get the direct institution IDs for a collection.
   * These are institutions where the user has direct duties.
   */
  const getDirectInstitutionIds = (collection: string): string[] => {
    return config.value?.collections?.[collection]?.directInstitutionIds || [];
  };

  return {
    // State
    config,
    isConfigLoading,
    configError,
    searchState,
    isRateLimited,

    // Computed
    isReady,
    hasExpired,
    accessibleCollections,
    hasAnyAccess,

    // Methods
    fetchConfig,
    refreshConfig,
    search,
    searchMeetings,
    searchAgendaItems,
    multiSearch,
    initialize,

    // Faceted search methods
    searchWithFacets,
    loadInitialFacets,

    // Per-collection helpers
    getCollectionApiKey,
    hasCollectionAccess,
    getCollectionTenantIds,
    isSuperAdmin,

    // Related institution helpers
    isFromRelatedInstitution,
    getDirectInstitutionIds,
  };
};

export type AdminSearchController = ReturnType<typeof useAdminSearch>;
