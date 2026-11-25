import { computed, getCurrentInstance, onMounted, onScopeDispose, ref, watch, type Ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';

import { provideFacetSearch, type FacetOption as FacetCount, type RawFacet } from '@/Search/core/facets';
import { SearchClientFactory } from '@/Search/core/services/SearchClientFactory';

import { readSearchParams, replaceSearchParams } from '../searchUrl';

export interface FacetOption extends FacetCount {
  label: string;
  count: number;
}

export type ListingSearchSort = 'relevance' | 'date_asc' | 'date_desc';

interface ListingSearchOptions<TDocument, TItem> {
  collection: string;
  perPage: number;
  initialItems?: TItem[];
  initialTotal?: number;
  unavailableMessage?: string;
  filters: Record<string, Ref<string[]>>;
  additionalFilterSources?: Ref<unknown>[];
  facets: Record<string, Ref<FacetOption[]>>;
  readUrl?: (params: URLSearchParams) => void;
  writeUrl?: (params: URLSearchParams) => void;
  buildFilters: (locale: string, excludeField?: string) => string[];
  buildSort: (sort: ListingSearchSort, hasQuery: boolean) => string;
  queryBy: string;
  queryByWeights: string;
  mapDocument: (document: TDocument, locale: string) => TItem;
}

export function listingDateSort(field: string, sort: ListingSearchSort, hasQuery: boolean, defaultDirection: 'asc' | 'desc'): string {
  if (sort === 'date_asc') return `${field}:asc`;
  if (sort === 'date_desc') return `${field}:desc`;
  const dateSort = `${field}:${defaultDirection}`;
  return hasQuery ? `_text_match:desc,${dateSort}` : dateSort;
}

function updateFacetOptions(targets: ReadonlyMap<string, Ref<FacetOption[]>>, facets: RawFacet[] = []): void {
  for (const { field_name: field, counts } of facets) {
    const target = targets.get(field);
    if (!target) continue;
    const options = counts.map(({ value, count }) => ({ label: String(value), value: String(value), count }));
    if (field === 'year') options.sort((a, b) => Number(b.value) - Number(a.value));
    target.value = options;
  }
}

export function useListingSearch<TDocument, TItem>(options: ListingSearchOptions<TDocument, TItem>) {
  const page = usePage();
  const locale = computed(() => page.props.app?.locale || 'lt');
  const search = computed(() =>
    SearchClientFactory.resolvePublicSearch<TDocument>(page.props.typesenseConfig, options.collection, locale.value),
  );

  const params = readSearchParams();
  const query = ref(params.get('q') || '');
  const sortParam = params.get('sort');
  const sortBy = ref<ListingSearchSort>(sortParam === 'date_asc' || sortParam === 'date_desc' ? sortParam : 'relevance');
  const filterEntries = Object.entries(options.filters);
  const facetTargets = new Map(Object.entries(options.facets));
  const facetFields = [...facetTargets.keys()];
  for (const [name, filter] of filterEntries) {
    const value = params.get(name);
    if (value) filter.value = value.split(',').filter(Boolean);
  }
  options.readUrl?.(params);

  const items = ref<TItem[]>(options.initialItems ?? []) as Ref<TItem[]>;
  const totalHits = ref(options.initialTotal ?? items.value.length);
  const currentPage = ref(1);
  const isLoading = ref(false);
  const isLoadingMore = ref(false);
  const error = ref<string | null>(null);
  const hasMore = computed(() => items.value.length < totalHits.value);

  let activeRequest: AbortController | null = null;
  let canAppend = true;

  provideFacetSearch(async (field, text, signal) => search.value?.client.searchFacet(field, text, signal) ?? []);

  function syncUrlParams() {
    const params = new URLSearchParams();
    if (query.value.trim()) params.set('q', query.value.trim());
    for (const [name, filter] of filterEntries) {
      if (filter.value.length) params.set(name, filter.value.join(','));
    }
    options.writeUrl?.(params);
    if (sortBy.value !== 'relevance') params.set('sort', sortBy.value);
    replaceSearchParams(params);
  }

  async function performSearch(append = false) {
    if (append && (!canAppend || activeRequest)) return;
    if (!append) debouncedSearch.cancel();

    const connection = search.value;
    if (!connection) {
      error.value = options.unavailableMessage ?? null;
      return;
    }

    activeRequest?.abort();
    const controller = new AbortController();
    activeRequest = controller;
    isLoading.value = !append;
    isLoadingMore.value = append;
    error.value = null;
    if (!append) {
      canAppend = false;
      currentPage.value = 1;
    }

    const targetPage = append ? currentPage.value + 1 : 1;
    const requestLocale = locale.value;
    const searchQuery = query.value.trim() || '*';

    try {
      const response = await connection.client.search(connection.collection, {
        q: searchQuery,
        query_by: options.queryBy,
        query_by_weights: options.queryByWeights,
        filter_by: options.buildFilters(requestLocale).join(' && '),
        sort_by: options.buildSort(sortBy.value, searchQuery !== '*'),
        facetFilters: Object.fromEntries(facetFields.map(field => [field, options.buildFilters(requestLocale, field).join(' && ')])),
        facet_by: facetFields.join(','),
        max_facet_values: 50,
        per_page: options.perPage,
        page: targetPage,
        prefix: true,
        infix: 'fallback',
        prioritize_exact_match: true,
        prioritize_token_position: true,
      }, controller.signal);
      if (activeRequest !== controller || controller.signal.aborted) return;

      const hits = (response.hits ?? []).map(({ document }) => options.mapDocument(document, requestLocale));
      items.value = append ? [...items.value, ...hits] : hits;
      totalHits.value = response.found ?? 0;
      currentPage.value = targetPage;
      canAppend = true;
      updateFacetOptions(facetTargets, response.facet_counts);
      syncUrlParams();
    }
    catch (err: unknown) {
      if (controller.signal.aborted || activeRequest !== controller) return;
      if (err instanceof Error && err.name === 'AbortError') return;
      error.value = err instanceof Error ? err.message : 'Search failed';
    }
    finally {
      if (activeRequest === controller) {
        activeRequest = null;
        isLoading.value = false;
        isLoadingMore.value = false;
      }
    }
  }

  const debouncedSearch = useDebounceFn(() => performSearch(), 300);

  function invalidateResults() {
    canAppend = false;
    activeRequest?.abort();
  }

  watch(query, () => {
    invalidateResults();
    debouncedSearch();
  }, { flush: 'sync' });

  const filterSources = [sortBy, ...Object.values(options.filters), ...(options.additionalFilterSources ?? [])];
  watch(filterSources, invalidateResults, { deep: true, flush: 'sync' });
  watch(filterSources, () => performSearch(), { deep: true });

  if (getCurrentInstance()) onMounted(() => performSearch());
  onScopeDispose(() => {
    activeRequest?.abort();
    debouncedSearch.cancel();
  }, true);

  const loadMore = () => {
    if (hasMore.value) performSearch(true);
  };

  return { query, sortBy, items, totalHits, currentPage, isLoading, isLoadingMore, hasMore, error, loadMore, performSearch };
}
