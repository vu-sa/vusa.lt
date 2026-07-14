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
const MAX_RECENT = 8;

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
    configKey: 'news',
    fallbackName: 'news',
    buildParams: query => ({
      query_by: 'title,short',
      query_by_weights: '10,4',
      sort_by: hasRealQuery(query) ? '_text_match:desc,publish_time:desc' : 'publish_time:desc',
    }),
  },
  {
    id: 'pages',
    configKey: 'pages',
    fallbackName: 'pages',
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

export const usePublicMultiSearch = (options: { perPage?: number } = {}) => {
  const perPage = options.perPage ?? DEFAULT_PER_PAGE;
  const page = usePage();

  const query = ref('');
  const isSearching = ref(false);
  const searchError = ref<string | null>(null);

  /** Which collections to include — persisted so the user's choice sticks. */
  const enabledCollections = useLocalStorage<SearchCollectionId[]>(
    'public-search-collections',
    [...ALL_IDS],
  );

  const recentSearches = useLocalStorage<string[]>('public-search-recent', []);

  const sections = reactive(
    Object.fromEntries(COLLECTIONS.map(c => [c.id, createEmptySection()])) as Record<SearchCollectionId, SectionState>,
  );

  let abortController: AbortController | null = null;

  const config = (): TypesenseConfig | undefined => page.props.typesenseConfig as TypesenseConfig | undefined;
  const locale = (): string => (page.props.app as { locale?: string })?.locale || 'lt';

  const isEnabled = (id: SearchCollectionId): boolean => enabledCollections.value.includes(id);

  const collectionName = (def: CollectionDef): string => config()?.collections?.[def.configKey] || def.fallbackName;

  const buildSearch = (def: CollectionDef, q: string, pageNum: number): Record<string, unknown> => ({
    collection: collectionName(def),
    q: q || '*',
    per_page: perPage,
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

  const resetSections = (): void => {
    for (const id of ALL_IDS) {
      Object.assign(sections[id], createEmptySection());
    }
  };

  const addRecentSearch = (q: string): void => {
    const trimmed = q.trim();
    if (!trimmed || trimmed === '*') {
      return;
    }
    recentSearches.value = [trimmed, ...recentSearches.value.filter(s => s !== trimmed)].slice(0, MAX_RECENT);
  };

  const performSearch = async (q: string): Promise<void> => {
    if (abortController) {
      abortController.abort();
    }

    const trimmed = q.trim();
    if (trimmed.length < MIN_QUERY_LENGTH) {
      resetSections();
      isSearching.value = false;
      searchError.value = null;
      return;
    }

    abortController = new AbortController();
    const { signal } = abortController;

    const enabled = COLLECTIONS.filter(def => isEnabled(def.id));
    if (enabled.length === 0) {
      resetSections();
      isSearching.value = false;
      return;
    }

    isSearching.value = true;
    searchError.value = null;

    try {
      const searches = enabled.map(def => buildSearch(def, trimmed, 1));
      const data = await runMultiSearch(searches, signal);

      enabled.forEach((def, index) => {
        applyResult(def.id, data.results?.[index], false);
      });

      // Clear any collections that are toggled off.
      for (const def of COLLECTIONS) {
        if (!isEnabled(def.id)) {
          Object.assign(sections[def.id], createEmptySection());
        }
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

    if (immediate) {
      addRecentSearch(q);
      debouncedSearch.cancel();
      performSearch(q);
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

  const toggleCollection = (id: SearchCollectionId): void => {
    enabledCollections.value = isEnabled(id)
      ? enabledCollections.value.filter(c => c !== id)
      : [...enabledCollections.value, id];

    if (query.value.trim().length >= MIN_QUERY_LENGTH) {
      performSearch(query.value);
    }
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
      .filter(def => isEnabled(def.id) && sections[def.id].totalHits > 0)
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
    ALL_IDS.reduce((sum, id) => (isEnabled(id) ? sum + sections[id].totalHits : sum), 0),
  );

  const hasAnyResults = computed(() => orderedSections.value.length > 0);

  // Shape compatible with `useSearchInterface`'s `SearchInterfaceController`.
  const filters = computed(() => ({ query: query.value }));
  const searchState = computed(() => ({ query: query.value }));
  const clearFilters = (): void => {
    query.value = '';
    resetSections();
  };

  return {
    // State
    query,
    isSearching,
    searchError,
    sections,
    enabledCollections,
    recentSearches,

    // Computed
    orderedSections,
    totalResultCount,
    hasAnyResults,

    // Actions
    search,
    loadMore,
    toggleCollection,
    isEnabled,

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
  };
};

export type PublicMultiSearchController = ReturnType<typeof usePublicMultiSearch>;
