import { ref, computed, watch } from 'vue';

import { useAdminSearchSession } from '@/Features/Admin/AdminSearch/searchSession';
import { buildAdminSearchParams, ADMIN_SEARCH_FALLBACKS } from '@/Features/Admin/AdminSearch/Config/collectionSearchConfig';

import { documentWithMatch, type HighlightedHit } from '@/Shared/Search/matches';
import { facetSearches, mergeDisjunctiveFacets } from '@/Shared/Search/facets';
import { ErrorUtils } from '@/Shared/Search/services/SearchErrorUtils';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';
import type { MultiSearchResults } from '@/Shared/Search/types';

export type { MultiSearchResults } from '@/Shared/Search/types';

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
  [key: string]: unknown;
}

/** The part of a Typesense search result the admin search reads. */
interface CollectionSearchResult {
  hits?: HighlightedHit[];
  found?: number;
  page?: number;
  facet_counts?: Array<{ field_name: string; counts: Array<{ value: string; count: number }> }>;
}

interface SearchState {
  results: AdminSearchResult[];
  isLoading: boolean;
  error: string | null;
  totalHits: number;
  query: string;
}

export const useAdminSearch = () => {
  const session = useAdminSearchSession();
  const { config, isConfigLoading, configError, rateLimitedUntil, isRateLimited, hasExpired, fetchConfig, refreshConfig } = session;
  let searchAbortController: AbortController | null = null;

  // Search state
  const searchState = ref<SearchState>({
    results: [],
    isLoading: false,
    error: null,
    totalHits: 0,
    query: '',
  });

  watch(session.generation, () => {
    searchAbortController?.abort();
    searchState.value = { results: [], isLoading: false, error: null, totalHits: 0, query: '' };
  }, { flush: 'sync' });

  // Computed
  const isReady = computed(() => config.value !== null && !configError.value);
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

  /** Own and authorized related institutions the scoped key grants for a collection. */
  const getCollectionInstitutionIds = (collection: string): string[] => {
    return config.value?.collections?.[collection]?.institutionIds ?? [];
  };

  const isSuperAdmin = computed(() => config.value?.isSuperAdmin ?? false);

  /**
   * One collection search sent as POST /multi_search. A GET query string is capped at 4000
   * characters, which a long `filter_by` (a "Sekamos" list of ~150+ ids) exceeds with a 400.
   * The per-search key keeps the collection's scoped filter; the header key only opens the endpoint.
   */
  const postCollectionSearch = async (
    baseUrl: string,
    collectionName: string,
    apiKey: string,
    params: URLSearchParams,
    signal?: AbortSignal,
    facetFilters: Record<string, string> = {},
    initialFacetFilterBy?: string,
  ): Promise<{ status: number; initialFacets?: CollectionSearchResult['facet_counts']; data?: CollectionSearchResult; errorText?: string; retryAfter?: string | null }> => {
    const base = Object.entries(config.value?.collections ?? {}).find(([, entry]) => entry.name === collectionName)?.[0] ?? collectionName;
    const requestConfig = config.value;
    const generation = session.generation.value;
    const profiled = buildAdminSearchParams(base, requestConfig, Object.fromEntries(params));
    const searches: Array<Record<string, unknown>> = facetSearches(profiled, facetFilters).map(search => ({ 'collection': collectionName, 'x-typesense-api-key': apiKey, ...search }));
    const needsFacetUniverse = initialFacetFilterBy !== undefined
      && (String(profiled.q).trim() !== '*' || String(profiled.filter_by ?? '') !== initialFacetFilterBy);
    if (needsFacetUniverse) {
      searches.push({ ...searches[0], q: '*', filter_by: initialFacetFilterBy, per_page: 0, page: 1, max_facet_values: 100 });
    }
    const response = await fetch(`${baseUrl}/multi_search`, {
      method: 'POST',
      headers: {
        'X-TYPESENSE-API-KEY': config.value?.headerKey || apiKey,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        searches,
      }),
      signal,
    });

    session.assertCurrent(generation);
    if (!response.ok) {
      if (response.status === 429) rateLimitedUntil.value = Date.now() + parseInt(response.headers.get('retry-after') || '5', 10) * 1000;
      return { status: response.status, errorText: await response.text(), retryAfter: response.headers.get('retry-after') };
    }

    const results = (await response.json())?.results ?? [];
    session.assertCurrent(generation);
    const result = results[0];

    // multi_search answers 200 and reports each search's failure inside its result.
    if (!result || result.error) {
      if (result?.code === 429) rateLimitedUntil.value = Date.now() + 5000;
      return { status: result?.code ?? 500, errorText: result?.error ?? 'Empty search response' };
    }

    const universe = needsFacetUniverse ? results.at(-1) : result;
    const failedFacet = results.slice(1).find((result: { error?: string; code?: number }) => result.error);
    if (failedFacet) {
      if (failedFacet.code === 429) rateLimitedUntil.value = Date.now() + 5000;
      return { status: failedFacet.code ?? 500, errorText: failedFacet.error };
    }
    const initialFacets = initialFacetFilterBy !== undefined ? universe?.facet_counts ?? [] : undefined;
    if (Object.keys(facetFilters).length) result.facet_counts = mergeDisjunctiveFacets(result.facet_counts ?? [], Object.keys(facetFilters), results.slice(1));
    return { status: 200, data: result, initialFacets };
  };

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
      _retryCount?: number;
    } = {},
  ): Promise<void> => {
    if (isRateLimited.value) {
      searchState.value.error = 'Too many requests. Please wait a moment.';
      return;
    }

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
        ...(options.queryBy ? { query_by: options.queryBy } : {}),
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

      const response = await postCollectionSearch(baseUrl, collectionName, apiKey, searchParams);

      if (response.status !== 200) {
        // Handle 401 - expired or invalid key
        if (response.status === 401 && (options._retryCount ?? 0) < 1) {
          await refreshConfig();
          // Retry the search once
          if (config.value) {
            return search(collection, query, { ...options, _retryCount: 1 });
          }
        }

        throw new Error(`Typesense error: ${response.status} - ${response.errorText}`);
      }

      const data = response.data ?? {};

      searchState.value.results = (data.hits?.map(hit => documentWithMatch(hit)) || []) as AdminSearchResult[];
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

    // Cancel any in-flight search
    if (searchAbortController) {
      searchAbortController.abort();
    }
    searchAbortController = new AbortController();
    const { signal } = searchAbortController;

    // If user has no access to any collections, return empty results immediately
    if (!hasAnyAccess.value) {
      return emptyResults();
    }

    const node = config.value.nodes?.[0];
    if (!node) {
      throw new Error('No Typesense nodes configured');
    }

    const baseUrl = `${node.protocol}://${node.host}:${node.port}`;

    const requestConfig = config.value;
    const generation = session.generation.value;
    const limits: Record<string, number> = {
      meetings: meetingsLimit, agenda_items: agendaItemsLimit, news: newsLimit,
      pages: pagesLimit, calendar: calendarLimit, institutions: institutionsLimit,
      documents: documentsLimit, resources: resourcesLimit, duties: dutiesLimit, users: usersLimit,
    };
    const searches: Array<Record<string, unknown>> = [];
    const searchOrder: string[] = [];
    for (const collection of Object.keys(ADMIN_SEARCH_FALLBACKS)) {
      const entry = requestConfig.collections[collection];
      if (!entry?.hasAccess || !entry.key) continue;
      searches.push(buildAdminSearchParams(collection, requestConfig, {
        collection: entry.name,
        q: query || '*',
        per_page: limits[collection],
        'x-typesense-api-key': entry.key,
      }));
      searchOrder.push(collection);
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

      session.assertCurrent(generation);
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
          throw new Error('Paieškos autentifikacija nepavyko. Pabandyk perkrauti puslapį.');
        }

        // Handle 403 - forbidden (scoped key doesn't allow this operation)
        if (response.status === 403) {
          const errorText = await response.text();
          console.error('Search forbidden:', errorText);
          throw new Error('Neturi prieigos prie paieškos. Pabandyk perkrauti puslapį.');
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
      session.assertCurrent(generation);

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
        // The int64 score exceeds JS's safe-integer range; keep it as a string for BigInt comparison.
        const hits = searchResult?.hits?.map(
          (hit: {
            document: Record<string, unknown>;
            highlights?: HighlightedHit['highlights'];
            text_match?: number;
            text_match_info?: { score?: string; tokens_matched?: number };
          }) => ({
            ...documentWithMatch(hit),
            _text_match: hit.text_match_info?.score
              ?? (hit.text_match != null ? String(hit.text_match) : '0'),
            _tokens_matched: hit.text_match_info?.tokens_matched ?? 0,
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
      if (searchAbortController?.signal === signal) searchAbortController = null;
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
      facetFilters?: Record<string, string>;
      includeInitialFacets?: boolean;
      initialFacetFilterBy?: string;
      sortBy?: string; // Sort order
      facetBy: string; // Comma-separated facet fields
      queryBy?: string; // Fields to search (defaults by collection)
      perPage?: number;
      page?: number;
      _retryCount?: number; // Internal retry counter
    },
  ): Promise<{
    hits: T[];
    initialFacets?: NonNullable<CollectionSearchResult['facet_counts']>;
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

    // Cancel any in-flight search
    if (searchAbortController) {
      searchAbortController.abort();
    }
    searchAbortController = new AbortController();
    const { signal } = searchAbortController;

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
      ...(queryBy ? { query_by: queryBy } : {}),
      per_page: String(perPage),
      page: String(page),
      facet_by: facetBy,
      max_facet_values: options.includeInitialFacets ? '100' : '50',
    });

    if (filterBy) {
      searchParams.append('filter_by', filterBy);
    }

    if (sortBy) {
      searchParams.append('sort_by', sortBy);
    }

    try {
      const response = await postCollectionSearch(baseUrl, collectionName, apiKey, searchParams, signal, options.facetFilters, options.includeInitialFacets ? options.initialFacetFilterBy ?? '' : undefined);

      if (response.status !== 200) {
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
          const retryAfter = parseInt(response.retryAfter || '5', 10);
          rateLimitedUntil.value = Date.now() + retryAfter * 1000;
          throw new Error('Too many requests. Please wait a moment.');
        }

        throw new Error(`Search failed: ${response.status} - ${response.errorText}`);
      }

      if (signal.aborted) throw new DOMException('Search was cancelled', 'AbortError');
      const data = response.data ?? {};

      return {
        hits: (data.hits?.map(hit => documentWithMatch(hit)) || []) as T[],
        initialFacets: response.initialFacets,
        totalHits: data.found || 0,
        facets: data.facet_counts || [],
        page: data.page || page,
        totalPages: Math.ceil((data.found || 0) / perPage),
      };
    }
    catch (error: unknown) {
      // Don't throw for aborted requests
      if (ErrorUtils.isAbortError(error)) {
        throw error;
      }
      // Re-throw with consistent error handling
      const searchError = ErrorUtils.fromError(error, 'faceted-search');
      throw new Error(searchError.userMessage);
    }
    finally {
      if (searchAbortController?.signal === signal) searchAbortController = null;
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
      ...(options.queryBy ? { query_by: options.queryBy } : {}),
      per_page: '0', // Don't need actual results
      facet_by: facetBy,
      max_facet_values: '100',
    });

    if (options.filterBy) {
      searchParams.append('filter_by', options.filterBy);
    }

    try {
      const response = await postCollectionSearch(baseUrl, collectionName, apiKey, searchParams);

      if (response.status !== 200) {
        if (response.status === 401 && _retryCount < 1) {
          await refreshConfig();
          const refreshedKey = getCollectionApiKey(collection);
          if (config.value && refreshedKey) {
            return loadInitialFacets(collection, facetBy, { ...options, _retryCount: _retryCount + 1 });
          }
        }

        throw new Error(`Failed to load facets: ${response.status} - ${response.errorText}`);
      }

      return response.data?.facet_counts || [];
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

  const searchFacetValues = async (collection: string, field: string, text: string, options: { query: string; filterBy?: string; queryBy?: string; signal?: AbortSignal }) => {
    if (isRateLimited.value) throw new Error('Too many requests. Please wait a moment.');
    if (!config.value) await fetchConfig();
    if (hasExpired.value) await refreshConfig();
    const cfg = config.value;
    const entry = cfg?.collections[collection];
    const node = cfg?.nodes[0];
    if (!entry?.key || !node) throw new Error('Search configuration unavailable');
    const params = new URLSearchParams({ q: options.query, ...(options.queryBy ? { query_by: options.queryBy } : {}), facet_by: field, facet_query: `${field}:${text}`, per_page: '0', max_facet_values: '100', filter_by: options.filterBy || '' });
    const response = await postCollectionSearch(`${node.protocol}://${node.host}:${node.port}`, entry.name, entry.key, params, options.signal);
    if (response.status !== 200) throw new Error(response.errorText || 'Facet search failed');
    return response.data?.facet_counts?.find(facet => facet.field_name === field)?.counts ?? [];
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
    searchFacetValues,
    loadInitialFacets,

    // Per-collection helpers
    getCollectionApiKey,
    hasCollectionAccess,
    getCollectionTenantIds,
    getCollectionInstitutionIds,
    isSuperAdmin,

    // Related institution helpers
    isFromRelatedInstitution,
    getDirectInstitutionIds,
  };
};
