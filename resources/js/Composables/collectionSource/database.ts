import { computed, getCurrentScope, onScopeDispose, ref, toValue, type MaybeRefOrGetter } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { useCollectionFilterMemory } from '@/Composables/collectionFilterMemory';

import type { CollectionFacet, CollectionSortOption, CollectionChip, CollectionSource } from './types';
import { MAX_RESTORED_PAGES, useOptimisticOverlay } from './shared';

interface DatabaseCollectionSourceOptions<T> {
  endpoint: string;
  initial: {
    items: T[];
    total: number;
    perPage: number;
    currentPage: number;
    lastPage: number;
  };
  /** A getter, so labels built with `$t()` follow translations that load after the page mounts. */
  sortOptions: MaybeRefOrGetter<CollectionSortOption[]>;
  defaultSort: string;
  preserveUrlKeys?: string[];
  /**
   * Filters the endpoint understands, sent as plain query params (`field=a` or `field[]=a`) and
   * as the `filters` JSON (PHP turns a dotted param like `tenant.id[]` into `tenant_id[]`, so
   * dotted keys only survive there), and carried in the page URL. Counts arrive once the filter
   * panel asks for them (CollectionFacetCounts on the endpoint).
   */
  facets?: DatabaseFacetDefinition[];
  /** No first page came with the page (a trash view): show the skeleton and fetch it now. */
  fetchOnMount?: boolean;
}

export interface DatabaseFacetDefinition {
  field: string;
  label: string;
  values: { value: string; label: string }[];
  /** A single-choice filter (`scope`): choosing a value replaces the previous one. */
  single?: boolean;
}

interface DatabaseCollectionResponse<T> {
  items: T[];
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  /** `{ field: { value: count } }`, present only when `include_facets` was sent. */
  facets?: Record<string, Record<string, number>> | null;
}

/** `?state=created,reserved` → `{ state: ['created', 'reserved'] }`, for the declared facets only. */
function readFiltersFromUrl(facets: DatabaseFacetDefinition[], params: URLSearchParams): Record<string, unknown> {
  const result: Record<string, unknown> = {};

  for (const facet of facets) {
    const raw = params.get(facet.field);
    const allowed = raw?.split(',').filter(value => facet.values.some(candidate => candidate.value === value)) ?? [];

    if (allowed.length > 0) {
      result[facet.field] = facet.single ? allowed[0] : allowed;
    }
  }

  return result;
}

/**
 * Database-backed collection source. Unlike the Typesense
 * adapter it starts with the Inertia payload, then refreshes through the admin API for search,
 * sorting and "Rodyti daugiau" without replacing the page's history state.
 */
export function useDatabaseCollectionSource<T>(options: DatabaseCollectionSourceOptions<T>): CollectionSource<T> {
  const filterMemory = useCollectionFilterMemory([...(options.facets ?? []).map(facet => facet.field), 'sort']);
  const fetchOnMount = options.fetchOnMount || filterMemory.restore().restored;
  const initialParams = new URLSearchParams(window.location.search);
  const items = ref<T[]>([...options.initial.items]);
  const total = ref(options.initial.total);
  const isLoading = ref(false);
  const isLoadingMore = ref(false);
  const hasMore = computed(() => currentPage.value < lastPage.value);
  const hasSearched = ref(!fetchOnMount);
  const error = ref<string | null>(null);
  const query = ref(initialParams.get('search') ?? '');
  const facetDefinitions = options.facets ?? [];
  const filters = ref<Record<string, unknown>>(readFiltersFromUrl(facetDefinitions, initialParams));
  const currentPage = ref(options.initial.currentPage);
  const lastPage = ref(options.initial.lastPage);
  const sortBy = ref(initialParams.get('sort') ?? options.defaultSort);
  const facetCounts = ref<Record<string, Record<string, number>> | null>(null);
  let facetsWanted = false;

  const selectedValues = (field: string): string[] => {
    const raw = filters.value[field];

    return (Array.isArray(raw) ? raw : raw === undefined || raw === '' ? [] : [raw]).map(String);
  };

  const facets = computed<CollectionFacet[]>(() =>
    facetDefinitions.map(facet => ({
      field: facet.field,
      label: facet.label,
      type: 'checkbox',
      values: facet.values.map(value => ({
        ...value,
        count: facetCounts.value?.[facet.field]?.[value.value],
        isSelected: selectedValues(facet.field).includes(value.value),
      })),
    })),
  );

  const chips = computed<CollectionChip[]>(() =>
    facetDefinitions.flatMap(facet =>
      selectedValues(facet.field).map(value => ({
        id: `${facet.field}:${value}`,
        label: `${facet.label}: ${facet.values.find(candidate => candidate.value === value)?.label ?? value}`,
      })),
    ),
  );
  const activeFilterCount = computed(() => chips.value.length);

  function setFilter(field: string, value: unknown): void {
    const next = { ...filters.value };

    if (value === undefined || value === '' || (Array.isArray(value) && value.length === 0)) {
      delete next[field];
    }
    else {
      next[field] = value;
    }

    filters.value = next;
    void fetchPage(1, false);
  }

  function toggleFilter(field: string, value: string): void {
    const current = selectedValues(field);

    if (facetDefinitions.find(facet => facet.field === field)?.single) {
      setFilter(field, current.includes(value) ? undefined : value);
      return;
    }

    setFilter(field, current.includes(value) ? current.filter(selected => selected !== value) : [...current, value]);
  }

  function syncUrl(): void {
    const url = new URL(window.location.href);
    const ownedKeys = ['search', 'sort', 'pages', ...facetDefinitions.map(facet => facet.field)];

    for (const key of ownedKeys) {
      url.searchParams.delete(key);
    }

    if (query.value.trim()) {
      url.searchParams.set('search', query.value.trim());
    }
    if (sortBy.value !== options.defaultSort) {
      url.searchParams.set('sort', sortBy.value);
    }
    if (currentPage.value > 1) {
      url.searchParams.set('pages', String(currentPage.value));
    }
    for (const facet of facetDefinitions) {
      const values = selectedValues(facet.field);

      if (values.length > 0) {
        url.searchParams.set(facet.field, values.join(','));
      }
    }

    window.history.replaceState(window.history.state, '', url.toString());
    filterMemory.remember();
  }

  function buildRequestUrl(page: number, perPage: number): URL {
    const [column, direction = 'asc'] = sortBy.value.split(':');
    const requestUrl = new URL(options.endpoint, window.location.origin);
    requestUrl.searchParams.set('page', String(page));
    requestUrl.searchParams.set('per_page', String(perPage));
    requestUrl.searchParams.set('sorting', JSON.stringify([{ id: column, desc: direction === 'desc' }]));
    if (query.value.trim()) {
      requestUrl.searchParams.set('search', query.value.trim());
    }
    for (const key of options.preserveUrlKeys ?? []) {
      const value = initialParams.get(key);
      if (value) {
        requestUrl.searchParams.set(key, value);
      }
    }
    for (const facet of facetDefinitions) {
      const values = selectedValues(facet.field);

      if (facet.single && values[0]) {
        requestUrl.searchParams.set(facet.field, values[0]);
      }
      else {
        values.forEach(value => requestUrl.searchParams.append(`${facet.field}[]`, value));
      }
    }
    if (Object.keys(filters.value).length > 0) {
      requestUrl.searchParams.set('filters', JSON.stringify(filters.value));
    }

    return requestUrl;
  }

  async function request(url: URL): Promise<DatabaseCollectionResponse<T>> {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const payload = await response.json() as { success: boolean; data?: DatabaseCollectionResponse<T>; message?: string };

    if (!response.ok || !payload.success || !payload.data) {
      throw new Error(payload.message ?? $t('Nepavyko įkelti sąrašo.'));
    }

    return payload.data;
  }

  // Two quick filter toggles start two requests; only the latest one may write its rows.
  let latestRequest = 0;

  async function fetchPage(page: number, append: boolean): Promise<void> {
    const current = ++latestRequest;

    if (append) {
      isLoadingMore.value = true;
    }
    else {
      isLoading.value = true;
    }
    error.value = null;

    try {
      const data = await request(buildRequestUrl(page, options.initial.perPage));

      if (current !== latestRequest) {
        return;
      }

      items.value = append ? [...items.value, ...data.items] : data.items;
      total.value = data.total;
      currentPage.value = data.current_page;
      lastPage.value = data.last_page;
      syncUrl();

      if (!append && facetsWanted) {
        void fetchFacetCounts();
      }
    }
    catch (cause) {
      if (current === latestRequest) {
        error.value = cause instanceof Error ? cause.message : $t('Nepavyko įkelti sąrašo.');
      }
    }
    finally {
      if (current === latestRequest) {
        isLoading.value = false;
        isLoadingMore.value = false;
        hasSearched.value = true;
      }
    }
  }

  let latestFacetRequest = 0;

  // A separate, one-row request, so the list never waits on the counting.
  async function fetchFacetCounts(): Promise<void> {
    if (facetDefinitions.length === 0) {
      return;
    }

    const current = ++latestFacetRequest;
    const url = buildRequestUrl(1, 1);
    url.searchParams.set('include_facets', '1');
    url.searchParams.set('facet_values', JSON.stringify(Object.fromEntries(
      facetDefinitions.map(facet => [facet.field, facet.values.map(value => value.value)]),
    )));
    url.searchParams.set('facet_single', JSON.stringify(facetDefinitions.filter(facet => facet.single).map(facet => facet.field)));

    try {
      const data = await request(url);

      if (current === latestFacetRequest) {
        facetCounts.value = data.facets ?? null;
      }
    }
    catch {
      // Counts are a nicety; the filters work without them.
    }
  }

  async function restorePages(refetch = fetchOnMount): Promise<void> {
    const wanted = Math.min(Number(initialParams.get('pages')) || 1, MAX_RESTORED_PAGES);

    if (refetch) {
      await fetchPage(1, false);
    }
    while (currentPage.value < wanted && currentPage.value < lastPage.value && !error.value) {
      await fetchPage(currentPage.value + 1, true);
    }
  }

  const debouncedRefresh = useDebounceFn(() => fetchPage(1, false), 250);

  void restorePages();
  if (getCurrentScope()) {
    onScopeDispose(router.on('navigate', (event) => {
      // History restores the original Inertia rows, while API filters only changed the URL.
      if (event.detail.type === 'history') void restorePages(true);
    }));
  }

  const overlay = useOptimisticOverlay<T>();

  return {
    items: computed(() => overlay.apply(items.value as T[])),
    total,
    isLoading,
    isLoadingMore,
    hasMore,
    hasSearched,
    error,
    query,
    filters,
    facets,
    chips,
    activeFilterCount,
    sortBy,
    sortOptions: computed(() => toValue(options.sortOptions)),
    search: (next, immediate = false) => {
      query.value = next;
      if (immediate) {
        void fetchPage(1, false);
      }
      else {
        void debouncedRefresh();
      }
    },
    toggleFilter,
    setFilter,
    clearFilters: () => {
      filters.value = {};
      void fetchPage(1, false);
    },
    clearChip: (id) => {
      const [field, ...rest] = id.split(':');
      toggleFilter(field, rest.join(':'));
    },
    replaceItems: (next) => { items.value = next; },
    patchItems: overlay.patchItems,
    hideItems: overlay.hideItems,
    setSortBy: (next) => {
      sortBy.value = next;
      void fetchPage(1, false);
    },
    loadMore: () => {
      if (hasMore.value && !isLoading.value && !isLoadingMore.value) {
        void fetchPage(currentPage.value + 1, true);
      }
    },
    refresh: () => void fetchPage(1, false),
    loadFacets: () => {
      if (!facetsWanted) {
        facetsWanted = true;
        void fetchFacetCounts();
      }
    },
  };
}
