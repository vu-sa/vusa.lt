/**
 * Public Multi-Search Composable
 *
 * Powers the unified public search page (`/paieska`, route `search`). Performs a single
 * Typesense `/multi_search` request across all public collections using the public
 * `search_only_key` shared via `page.props.typesenseConfig`, then keeps per-collection
 * "section" state (results, totals, pagination) so each section can render its existing
 * `*Results.vue` wrapper with its own button-based "load more".
 *
 * Modeled on `useAdminSearch.multiSearch()` but much simpler: a single public key, no
 * scoped-key generation, expiry, or refresh logic. Per-collection query parameters are
 * lifted from the dedicated search services (DocumentSearchService, InstitutionSearchService,
 * MeetingSearchService) so behavior stays consistent with the dedicated pages.
 */

import { reactive, ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';
import { debounce } from 'lodash-es';

import { ErrorUtils } from '@/Shared/Search/services/SearchErrorUtils';
import { trackEvent } from '@/Plugins/umami';

export type SearchCollectionId
  = | 'institutions'
    | 'meetings'
    | 'documents'
    | 'news'
    | 'pages'
    | 'calendar';

interface TypesenseNode {
  protocol: string;
  host: string;
  port: number;
  path?: string;
}

interface TypesenseConfig {
  apiKey: string;
  nodes: TypesenseNode[];
  collections?: Record<string, string>;
}

export interface SectionState {
  /** Raw Typesense documents (consumed directly by the `*Results.vue` wrappers). */
  hits: any[];
  totalHits: number;
  /** 1-based page currently loaded. */
  page: number;
  hasMore: boolean;
  isLoadingMore: boolean;
  /** Best `text_match` score in this section — used for relevance ordering across sections. */
  topScore: number;
}

interface CollectionDef {
  id: SearchCollectionId;
  /** Key into `typesenseConfig.collections` (logical collection name). */
  configKey: string;
  /** Fallback collection name if config mapping is missing. */
  fallbackName: string;
  /** Build the per-collection search parameters (query_by, sort_by, filters, …). */
  buildParams: (query: string, locale: string) => Record<string, unknown>;
}

const MIN_QUERY_LENGTH = 2;
const DEFAULT_PER_PAGE = 6;
const DEFAULT_FILTERED_PER_PAGE = 12;
const MAX_RECENT = 8;

/** Typesense wildcard — powers the "browse everything" state on page load and after clearing. */
const BROWSE_QUERY = '*';

/**
 * How long typing must settle before a term is reported to analytics. `performSearch` runs
 * behind a much shorter debounce so results feel live; reporting on that same cadence would
 * record every keystroke fragment ("v", "vu", "vusa") and bury the terms people meant.
 */
const REPORT_SETTLE_MS = 2000;

/** Search terms are free text, so they are capped before leaving the browser. */
const MAX_REPORTED_TERM_LENGTH = 60;

const normalizeReportedTerm = (term: string): string =>
  term.trim().toLowerCase().slice(0, MAX_REPORTED_TERM_LENGTH);

/** Fixed tiebreaker order when relevance scores are equal. */
const PRIORITY: SearchCollectionId[] = [
  'institutions',
  'meetings',
  'documents',
  'news',
  'pages',
  'calendar',
];

const hasRealQuery = (query: string): boolean => Boolean(query) && query.trim() !== '' && query.trim() !== '*';

const COLLECTIONS: CollectionDef[] = [
  {
    id: 'institutions',
    configKey: 'public_institutions',
    fallbackName: 'public_institutions',
    buildParams: (query, locale) => ({
      query_by: locale === 'en'
        ? 'name_en,name_lt,short_name_en,short_name_lt,alias'
        : 'name_lt,name_en,short_name_lt,short_name_en,alias',
      query_by_weights: '10,8,6,4,3',
      sort_by: hasRealQuery(query)
        ? '_text_match:desc,has_logo:desc,name_lt:asc'
        : (locale === 'en' ? 'has_logo:desc,name_en:asc,name_lt:asc' : 'has_logo:desc,name_lt:asc,name_en:asc'),
      prefix: true,
      infix: 'fallback',
      prioritize_exact_match: true,
      prioritize_token_position: true,
      typo_tokens_threshold: 2,
      min_len_1typo: 3,
      min_len_2typo: 6,
      drop_tokens_threshold: 5,
    }),
  },
  {
    id: 'meetings',
    configKey: 'public_meetings',
    fallbackName: 'public_meetings',
    buildParams: (query, locale) => ({
      query_by: `title,description,institution_name_${locale}`,
      query_by_weights: '10,5,3',
      sort_by: hasRealQuery(query) ? '_text_match:desc,start_time:desc' : 'start_time:desc',
    }),
  },
  {
    id: 'documents',
    configKey: 'documents',
    fallbackName: 'documents',
    buildParams: query => ({
      query_by: 'title,summary,content_type,document_year,document_date_formatted',
      query_by_weights: '10,3,2,6,4',
      sort_by: hasRealQuery(query) ? '_text_match:desc,document_date:desc' : 'document_date:desc,created_at:desc',
      filter_by: 'is_active:=true',
      prefix: false,
      infix: 'fallback',
      prioritize_exact_match: true,
      prioritize_token_position: true,
      typo_tokens_threshold: 2,
      min_len_1typo: 4,
      min_len_2typo: 7,
      drop_tokens_threshold: 10,
    }),
  },
  {
    id: 'news',
    configKey: 'public_news',
    fallbackName: 'public_news',
    buildParams: query => ({
      query_by: 'title,short',
      query_by_weights: '10,4',
      sort_by: hasRealQuery(query) ? '_text_match:desc,publish_time:desc' : 'publish_time:desc',
    }),
  },
  {
    id: 'pages',
    configKey: 'public_pages',
    fallbackName: 'public_pages',
    buildParams: query => ({
      query_by: 'title',
      sort_by: hasRealQuery(query) ? '_text_match:desc,created_at:desc' : 'created_at:desc',
    }),
  },
  {
    id: 'calendar',
    configKey: 'calendar',
    fallbackName: 'calendar',
    buildParams: query => ({
      query_by: 'title,title_lt,title_en',
      query_by_weights: '10,8,8',
      sort_by: hasRealQuery(query) ? '_text_match:desc,date:desc' : 'date:desc',
    }),
  },
];

const ALL_IDS = COLLECTIONS.map(c => c.id);

const createEmptySection = (): SectionState => ({
  hits: [],
  totalHits: 0,
  page: 1,
  hasMore: false,
  isLoadingMore: false,
  topScore: 0,
});

export const usePublicMultiSearch = (options: { perPage?: number; filteredPerPage?: number } = {}) => {
  // Small preview size when browsing across all six collections at once...
  const perPage = options.perPage ?? DEFAULT_PER_PAGE;
  // ...but once the user filters down to specific types, they expect a real result list for
  // those types rather than the same 3-item preview.
  const filteredPerPage = options.filteredPerPage ?? DEFAULT_FILTERED_PER_PAGE;
  const page = usePage();

  const query = ref('');
  /** What the search box should show — `''` while browsing, since `query` holds `'*'` then. */
  const displayQuery = ref('');
  const isSearching = ref(false);
  const searchError = ref<string | null>(null);
  const hasSearched = ref(false);

  /**
   * Collection type filter — an additive OR filter, same model as `DocumentSearchFilters.contentTypes`.
   * Empty means "no filter, show everything"; checking one or more narrows the results down to just
   * those types. Persisted so the user's choice sticks.
   */
  const enabledCollections = useLocalStorage<SearchCollectionId[]>(
    'public-search-collections',
    [],
  );

  const recentSearches = useLocalStorage<string[]>('public-search-recent', []);

  const sections = reactive(
    Object.fromEntries(COLLECTIONS.map(c => [c.id, createEmptySection()])) as Record<SearchCollectionId, SectionState>,
  );

  let abortController: AbortController | null = null;

  const config = (): TypesenseConfig | undefined => page.props.typesenseConfig as TypesenseConfig | undefined;
  const locale = (): string => (page.props.app as { locale?: string })?.locale || 'lt';

  /** Whether the collection's checkbox is checked (not the same as whether it's currently shown). */
  const isEnabled = (id: SearchCollectionId): boolean => enabledCollections.value.includes(id);

  const hasCollectionFilter = (): boolean => enabledCollections.value.length > 0;

  /** Whether a collection's results should currently be displayed — everything, unless filtered. */
  const isVisible = (id: SearchCollectionId): boolean => !hasCollectionFilter() || isEnabled(id);

  const collectionName = (def: CollectionDef): string => config()?.collections?.[def.configKey] || def.fallbackName;

  /**
   * Hidden collections (filtered out) only need enough hits to keep their `found` count for the
   * sidebar badge accurate — visible ones get the real page size, larger once filtered down to
   * fewer types so picking a type returns an actual result list instead of the browse preview.
   */
  const collectionPerPage = (id: SearchCollectionId): number => {
    if (!isVisible(id)) {
      return 1;
    }
    return hasCollectionFilter() ? filteredPerPage : perPage;
  };

  const buildSearch = (def: CollectionDef, q: string, pageNum: number): Record<string, unknown> => ({
    collection: collectionName(def),
    q: q || '*',
    per_page: collectionPerPage(def.id),
    page: pageNum,
    ...def.buildParams(q, locale()),
  });

  const runMultiSearch = async (
    searches: Array<Record<string, unknown>>,
    signal?: AbortSignal,
  ): Promise<{ results?: Array<{ hits?: Array<{ document: any; text_match?: number }>; found?: number; error?: string }> }> => {
    const cfg = config();
    const node = cfg?.nodes?.[0];
    if (!cfg?.apiKey || !node) {
      throw new Error('Typesense not configured');
    }

    const baseUrl = `${node.protocol}://${node.host}:${node.port}`;
    const response = await fetch(`${baseUrl}/multi_search`, {
      method: 'POST',
      headers: {
        'X-TYPESENSE-API-KEY': cfg.apiKey,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ searches }),
      signal,
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`Search request failed: ${response.status} - ${text}`);
    }

    return response.json();
  };

  const applyResult = (
    id: SearchCollectionId,
    result: { hits?: Array<{ document: any; text_match?: number }>; found?: number; error?: string } | undefined,
    append: boolean,
  ): void => {
    const section = sections[id];

    if (!result || result.error) {
      if (result?.error) {
        console.warn(`[publicSearch] ${id} search error:`, result.error);
      }
      if (!append) {
        Object.assign(section, createEmptySection());
      }
      return;
    }

    const hits = (result.hits ?? []).map(hit => hit.document);
    section.totalHits = result.found ?? 0;

    if (append) {
      section.hits = [...section.hits, ...hits];
    }
    else {
      section.hits = hits;
      section.page = 1;
      section.topScore = result.hits?.[0]?.text_match ?? 0;
    }

    section.hasMore = section.hits.length < section.totalHits;
  };

  const addRecentSearch = (q: string): void => {
    const trimmed = q.trim();
    if (!trimmed || trimmed === '*') {
      return;
    }
    recentSearches.value = [trimmed, ...recentSearches.value.filter(s => s !== trimmed)].slice(0, MAX_RECENT);
  };

  /**
   * The last term sent to analytics. Toggling a collection re-runs the same search, which
   * would otherwise report the term again and inflate its apparent popularity.
   */
  let lastReportedTerm = '';

  /**
   * Reports a settled search so we learn what people look for — and, when `results` is 0,
   * what they fail to find. Only the term and the result count are sent; see the privacy
   * note on the `/privatumas` page.
   */
  const reportSearch = debounce((term: string, results: number): void => {
    if (term === lastReportedTerm) {
      return;
    }

    lastReportedTerm = term;
    trackEvent('search_submitted', { term, results });
  }, REPORT_SETTLE_MS);

  const performSearch = async (q: string): Promise<void> => {
    const trimmed = q.trim();

    // Below the minimum length (but not the browse wildcard itself), fall back to browsing
    // everything rather than blanking the page — e.g. clearing the search box.
    if (trimmed !== BROWSE_QUERY && trimmed.length < MIN_QUERY_LENGTH) {
      lastReportedTerm = '';
      await performSearch(BROWSE_QUERY);
      return;
    }

    if (abortController) {
      abortController.abort();
    }

    abortController = new AbortController();
    const { signal } = abortController;

    isSearching.value = true;
    searchError.value = null;

    try {
      // Every collection is always queried (regardless of which are enabled) so the sidebar
      // can keep showing accurate counts for collections the user has toggled off.
      const searches = COLLECTIONS.map(def => buildSearch(def, trimmed, 1));
      const data = await runMultiSearch(searches, signal);

      COLLECTIONS.forEach((def, index) => {
        applyResult(def.id, data.results?.[index], false);
      });

      hasSearched.value = true;

      if (hasRealQuery(trimmed)) {
        reportSearch(normalizeReportedTerm(trimmed), totalResultCount.value);
      }
    }
    catch (error) {
      if (ErrorUtils.isAbortError(error)) {
        return;
      }
      searchError.value = ErrorUtils.fromError(error, 'multi-search').userMessage;
    }
    finally {
      isSearching.value = false;
      abortController = null;
    }
  };

  const debouncedSearch = debounce(performSearch, 300);

  const search = (q: string, immediate = false): void => {
    query.value = q;
    displayQuery.value = q === BROWSE_QUERY ? '' : q;

    if (immediate) {
      addRecentSearch(q);
      debouncedSearch.cancel();
      // Submitting is an explicit signal, so the term is reported without waiting to settle.
      void performSearch(q).then(() => reportSearch.flush());
    }
    else {
      debouncedSearch(q);
    }
  };

  const loadMore = async (id: SearchCollectionId): Promise<void> => {
    const section = sections[id];
    const def = COLLECTIONS.find(c => c.id === id);
    if (!def || section.isLoadingMore || !section.hasMore) {
      return;
    }

    section.isLoadingMore = true;
    try {
      const data = await runMultiSearch([buildSearch(def, query.value, section.page + 1)]);
      section.page += 1;
      applyResult(id, data.results?.[0], true);
    }
    catch (error) {
      if (!ErrorUtils.isAbortError(error)) {
        searchError.value = ErrorUtils.fromError(error, 'load-more').userMessage;
      }
    }
    finally {
      section.isLoadingMore = false;
    }
  };

  // Selecting/deselecting changes each collection's page size (see `collectionPerPage`), so a
  // refetch is needed — the previously-fetched hits aren't enough once a type becomes visible.
  const toggleCollection = (id: SearchCollectionId): void => {
    enabledCollections.value = isEnabled(id)
      ? enabledCollections.value.filter(c => c !== id)
      : [...enabledCollections.value, id];

    void performSearch(query.value);
  };

  /** Clears the collection filter back to "no filter, show everything". */
  const resetCollections = (): void => {
    enabledCollections.value = [];
    void performSearch(query.value);
  };

  const cancelPendingSearch = (): void => {
    debouncedSearch.cancel();
    if (abortController) {
      abortController.abort();
      abortController = null;
    }
  };

  const removeRecentSearch = (s: string): void => {
    recentSearches.value = recentSearches.value.filter(item => item !== s);
  };

  const clearRecentSearches = (): void => {
    recentSearches.value = [];
  };

  /** Section ids that currently have results, ordered by relevance (top score), then priority. */
  const orderedSections = computed<SearchCollectionId[]>(() =>
    COLLECTIONS
      .filter(def => isVisible(def.id) && sections[def.id].totalHits > 0)
      .map(def => def.id)
      .sort((a, b) => {
        const scoreDiff = sections[b].topScore - sections[a].topScore;
        if (scoreDiff !== 0) {
          return scoreDiff;
        }
        return PRIORITY.indexOf(a) - PRIORITY.indexOf(b);
      }),
  );

  const totalResultCount = computed(() =>
    ALL_IDS.reduce((sum, id) => (isVisible(id) ? sum + sections[id].totalHits : sum), 0),
  );

  const hasAnyResults = computed(() => orderedSections.value.length > 0);

  // Shape compatible with `useSearchInterface`'s `SearchInterfaceController`.
  const filters = computed(() => ({ query: query.value }));
  const searchState = computed(() => ({ query: query.value }));
  // Clearing the search box returns to browsing everything rather than an empty page.
  const clearFilters = (): void => {
    search(BROWSE_QUERY, true);
  };

  return {
    // State
    query,
    displayQuery,
    isSearching,
    searchError,
    sections,
    enabledCollections,
    recentSearches,
    hasSearched,

    // Computed
    orderedSections,
    totalResultCount,
    hasAnyResults,

    // Actions
    search,
    loadMore,
    toggleCollection,
    resetCollections,
    isEnabled,
    isVisible,

    // SearchInterfaceController compatibility
    filters,
    searchState,
    clearFilters,
    cancelPendingSearch,
    removeRecentSearch,
    clearRecentSearches,

    // Constants
    allCollectionIds: ALL_IDS,
    minQueryLength: MIN_QUERY_LENGTH,
    browseQuery: BROWSE_QUERY,
  };
};

export type PublicMultiSearchController = ReturnType<typeof usePublicMultiSearch>;
