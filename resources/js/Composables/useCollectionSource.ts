import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';
import { useDebounceFn } from '@vueuse/core';

import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import { getFacetValueLabel } from '@/Features/Admin/AdminSearch/Config/collectionFacetConfig';
import type { AdminCollection } from '@/Features/Admin/AdminSearch/Types/AdminSearchTypes';

export interface CollectionFacetValue {
  value: string;
  label: string;
  count: number;
  isSelected: boolean;
}

export interface CollectionFacet {
  field: string;
  label: string;
  type: 'checkbox' | 'year-pills';
  values: CollectionFacetValue[];
}

export interface CollectionSortOption {
  value: string;
  label: string;
}

/** One removable "this is filtering the list" chip. */
export interface CollectionChip {
  id: string;
  label: string;
}

/**
 * What `CollectionPage` needs from wherever the rows come from, so it never learns whether
 * they are a Typesense index or Inertia props. Typesense is the only implementation so far;
 * the database-backed one lands with Rezervacijos (PR 5.6), its first real consumer.
 */
export interface CollectionSource<T> {
  items: Readonly<Ref<readonly T[]>>;
  total: Readonly<Ref<number>>;
  isLoading: Readonly<Ref<boolean>>;
  isLoadingMore: Readonly<Ref<boolean>>;
  hasMore: Readonly<Ref<boolean>>;
  /** False until the first request has come back, so the first paint shows a skeleton, not "empty". */
  hasSearched: Readonly<Ref<boolean>>;
  error: Readonly<Ref<string | null>>;
  query: Readonly<Ref<string>>;
  filters: Readonly<Ref<Record<string, unknown>>>;
  facets: ComputedRef<CollectionFacet[]>;
  chips: ComputedRef<CollectionChip[]>;
  /** Filters in force, not counting the text query. */
  activeFilterCount: Readonly<Ref<number>>;
  sortBy: Readonly<Ref<string>>;
  sortOptions: ComputedRef<CollectionSortOption[]>;
  search: (query: string, immediate?: boolean) => void;
  toggleFilter: (field: string, value: string) => void;
  setFilter: (field: string, value: unknown) => void;
  clearFilters: () => void;
  clearChip: (id: string) => void;
  /** Local-only replacement for optimistic mutations. Search adapters may safely ignore it. */
  replaceItems: (items: T[]) => void;
  setSortBy: (value: string) => void;
  loadMore: () => void;
  refresh: () => void;
}

export interface TypesenseCollectionSource<T> extends CollectionSource<T> {
  /** Institutions where the user has a duty of their own, per the scoped search key. */
  directInstitutionIds: ComputedRef<string[]>;
}

interface DatabaseCollectionSourceOptions<T> {
  endpoint: string;
  initial: {
    items: T[];
    total: number;
    perPage: number;
    currentPage: number;
    lastPage: number;
  };
  sortOptions: CollectionSortOption[];
  defaultSort: string;
  preserveUrlKeys?: string[];
  /**
   * Filters the endpoint understands, sent as plain query params (`field=a` or `field[]=a`) and
   * carried in the page URL. Values have no counts: the database source does not facet.
   */
  facets?: DatabaseFacetDefinition[];
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
}

interface TypesenseSourceOptions {
  collection: AdminCollection;
  /** Query-string keys the page owns (`view`, `item`) that a new search must not wipe. */
  preserveUrlKeys?: string[];
  perPage?: number;
  /** Fields whose values are not meant to be read one by one (ids): one chip with this label. */
  collapsedChips?: Record<string, string>;
  /**
   * The readable name of a facet value where the page must agree with its status badges (U10).
   * Return undefined to fall back to the shared facet labels.
   */
  valueLabel?: (field: string, value: string) => string | undefined;
}

/** "Rodyti daugiau" loads this many at a time (.ai/rules/js-pages-admin.md). */
const PAGE_SIZE = 50;

export function useTypesenseCollectionSource<T = unknown>(options: TypesenseSourceOptions): TypesenseCollectionSource<T> {
  const controller = useAdminCollectionSearch({
    collection: options.collection,
    loadFacetsOnMount: true,
    searchOnMount: true,
    syncToUrl: true,
    preserveUrlKeys: options.preserveUrlKeys,
    perPage: options.perPage ?? PAGE_SIZE,
  });

  const labelOf = (field: string, value: string) => options.valueLabel?.(field, value) ?? getFacetValueLabel(field, value);

  const hasSearched = ref(false);
  watch(controller.status, (status, previous) => {
    if (previous === 'searching' && status !== 'searching') {
      hasSearched.value = true;
    }
  });
  // A failed first request would otherwise leave the skeleton up forever.
  watch(controller.error, (error) => {
    if (error) {
      hasSearched.value = true;
    }
  });

  const facets = computed<CollectionFacet[]>(() =>
    controller.facets.value
      .filter(facet => facet.type === 'checkbox' || facet.type === 'year-pills')
      .map(facet => ({
        field: facet.field,
        label: facet.label,
        type: facet.type as CollectionFacet['type'],
        values: facet.values.map(value => ({
          value: value.value,
          label: labelOf(facet.field, value.value),
          count: value.count,
          isSelected: value.isSelected,
        })),
      })),
  );

  const chips = computed<CollectionChip[]>(() => {
    const result: CollectionChip[] = [];

    for (const fieldConfig of controller.facetConfig.fields) {
      const raw = controller.filters.value[fieldConfig.field];
      const values = (Array.isArray(raw) ? raw : raw === undefined || raw === '' ? [] : [raw]).map(String);

      if (values.length === 0) {
        continue;
      }

      const collapsed = options.collapsedChips?.[fieldConfig.field];
      if (collapsed) {
        result.push({ id: fieldConfig.field, label: collapsed });
        continue;
      }

      for (const value of values) {
        result.push({
          id: `${fieldConfig.field}:${value}`,
          label: `${fieldConfig.label}: ${labelOf(fieldConfig.field, value)}`,
        });
      }
    }

    return result;
  });

  function clearChip(id: string): void {
    const [field, ...rest] = id.split(':');
    const value = rest.join(':');
    const current = controller.filters.value[field];

    if (value === '' || !Array.isArray(current)) {
      controller.setFilter(field, undefined);
      return;
    }

    controller.toggleFilter(field, field.includes('year') ? Number(value) : value);
  }

  return {
    directInstitutionIds: computed(() => controller.adminSearch.getDirectInstitutionIds(options.collection)),
    items: computed(() => controller.results.value as T[]),
    total: controller.totalHits,
    isLoading: controller.isSearching,
    isLoadingMore: controller.isLoadingMore,
    hasMore: controller.hasMoreResults,
    hasSearched,
    error: computed(() => controller.error.value?.userMessage ?? null),
    query: controller.query,
    filters: controller.filters as unknown as Ref<Record<string, unknown>>,
    facets,
    chips,
    activeFilterCount: computed(() => chips.value.length),
    sortBy: controller.sortBy,
    sortOptions: computed(() => controller.sortOptions.value.map(({ value, label }) => ({ value, label }))),
    search: controller.search,
    toggleFilter: (field, value) => controller.toggleFilter(field, field.includes('year') ? Number(value) : value),
    setFilter: controller.setFilter,
    clearFilters: controller.clearFilters,
    clearChip,
    replaceItems: () => undefined,
    setSortBy: controller.setSortBy,
    loadMore: () => void controller.loadMore(),
    refresh: () => void controller.refresh(),
  };
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
  const initialParams = new URLSearchParams(window.location.search);
  const items = ref<T[]>([...options.initial.items]);
  const total = ref(options.initial.total);
  const isLoading = ref(false);
  const isLoadingMore = ref(false);
  const hasMore = computed(() => currentPage.value < lastPage.value);
  const hasSearched = ref(true);
  const error = ref<string | null>(null);
  const query = ref(initialParams.get('search') ?? '');
  const facetDefinitions = options.facets ?? [];
  const filters = ref<Record<string, unknown>>(readFiltersFromUrl(facetDefinitions, initialParams));
  const currentPage = ref(options.initial.currentPage);
  const lastPage = ref(options.initial.lastPage);
  const sortBy = ref(initialParams.get('sort') ?? options.defaultSort);

  watch(() => options.initial.items, (newItems) => {
    if (newItems) {
      items.value = [...newItems];
      total.value = options.initial.total;
      currentPage.value = options.initial.currentPage;
      lastPage.value = options.initial.lastPage;
    }
  });

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
        count: 0,
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
  }

  // Two quick filter toggles start two requests; only the latest one may write its rows.
  let latestRequest = 0;

  async function fetchPage(page: number, append: boolean): Promise<void> {
    const request = ++latestRequest;

    if (append) {
      isLoadingMore.value = true;
    }
    else {
      isLoading.value = true;
    }
    error.value = null;

    const [column, direction = 'asc'] = sortBy.value.split(':');
    const requestUrl = new URL(options.endpoint, window.location.origin);
    requestUrl.searchParams.set('page', String(page));
    requestUrl.searchParams.set('per_page', String(options.initial.perPage));
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

    try {
      const response = await fetch(requestUrl, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json() as { success: boolean; data?: DatabaseCollectionResponse<T>; message?: string };

      if (request !== latestRequest) {
        return;
      }

      if (!response.ok || !payload.success || !payload.data) {
        throw new Error(payload.message ?? 'Nepavyko įkelti sąrašo.');
      }

      items.value = append ? [...items.value, ...payload.data.items] : payload.data.items;
      total.value = payload.data.total;
      currentPage.value = payload.data.current_page;
      lastPage.value = payload.data.last_page;
      syncUrl();
    }
    catch (cause) {
      if (request === latestRequest) {
        error.value = cause instanceof Error ? cause.message : 'Nepavyko įkelti sąrašo.';
      }
    }
    finally {
      if (request === latestRequest) {
        isLoading.value = false;
        isLoadingMore.value = false;
        hasSearched.value = true;
      }
    }
  }

  const debouncedRefresh = useDebounceFn(() => fetchPage(1, false), 250);

  return {
    items,
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
    sortOptions: computed(() => options.sortOptions),
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
    setSortBy: (next) => {
      sortBy.value = next;
      void fetchPage(1, false);
    },
    loadMore: () => {
      if (hasMore.value && !isLoadingMore.value) {
        void fetchPage(currentPage.value + 1, true);
      }
    },
    refresh: () => void fetchPage(1, false),
  };
}
