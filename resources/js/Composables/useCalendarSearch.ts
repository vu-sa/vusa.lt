import { ref, computed, watch, onMounted, onUnmounted, getCurrentInstance } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';

import { SearchClientFactory, type TypesenseClient } from '@/Shared/Search/services/SearchClientFactory';

export interface CalendarEventDocument {
  id: string | number;
  title: string;
  title_lt?: string;
  title_en?: string;
  description?: string;
  date: number;
  end_date?: number | null;
  lang?: string;
  tenant_id: number;
  tenant_ids?: number[];
  tenant_name?: string;
  tenant_shortname?: string;
  category_id?: number | null;
  category_name?: string | null;
  location?: string | null;
  is_all_day?: boolean;
  is_remote?: boolean;
  is_international?: boolean;
  main_image_url?: string | null;
  facebook_url?: string | null;
  cto_url?: string | null;
  created_at?: number;
}

export interface FacetOption {
  label: string;
  value: string;
  count: number;
}

export interface UseCalendarSearchOptions {
  initialTab?: CalendarSearchTab;
  perPage?: number;
}

export type CalendarSearchTab = 'upcoming' | 'past' | 'all';
export type CalendarSearchSort = 'relevance' | 'date_asc' | 'date_desc';

interface TypesensePageConfig {
  apiKey: string;
  nodes: Array<{ protocol: string; host: string; port: number }>;
  collections?: Record<string, string>;
}

export function useCalendarSearch(options: UseCalendarSearchOptions = {}) {
  const page = usePage();
  const perPage = options.perPage ?? 18;

  const query = ref('');
  const tab = ref<CalendarSearchTab>(options.initialTab ?? 'upcoming');
  const selectedCategories = ref<string[]>([]);
  const selectedTenants = ref<string[]>([]);
  const selectedYears = ref<string[]>([]);
  const isRemoteOnly = ref(false);
  const sortBy = ref<CalendarSearchSort>('relevance');

  const events = ref<CalendarEventDocument[]>([]);
  const totalHits = ref(0);
  const currentPage = ref(1);
  const isLoading = ref(false);
  const isLoadingMore = ref(false);
  const error = ref<string | null>(null);

  // Facets
  const categoryFacets = ref<FacetOption[]>([]);
  const tenantFacets = ref<FacetOption[]>([]);
  const yearFacets = ref<FacetOption[]>([]);

  let abortController: AbortController | null = null;
  let client: TypesenseClient | null = null;

  const locale = computed(() => (page.props.app as { locale?: string })?.locale || 'lt');
  const hasMore = computed(() => events.value.length < totalHits.value);
  const hasActiveFilters = computed(() =>
    Boolean(query.value.trim())
    || selectedCategories.value.length > 0
    || selectedTenants.value.length > 0
    || selectedYears.value.length > 0
    || isRemoteOnly.value,
  );
  const activeFilterCount = computed(() =>
    selectedCategories.value.length
    + selectedTenants.value.length
    + selectedYears.value.length
    + (isRemoteOnly.value ? 1 : 0),
  );

  const getClient = (): TypesenseClient | null => {
    if (client) return client;
    const config = page.props.typesenseConfig as TypesensePageConfig | undefined;
    if (!config?.apiKey || !config?.nodes?.length) {
      return null;
    }
    client = SearchClientFactory.createPublicClient(config);
    return client;
  };

  const getCollectionName = (): string => {
    const config = page.props.typesenseConfig as TypesensePageConfig | undefined;
    return config?.collections?.calendar || 'calendar';
  };

  const buildFilterConditions = (): string[] => {
    const conditions: string[] = [];
    const now = Math.floor(Date.now() / 1000);

    // Tab date condition
    if (tab.value === 'upcoming') {
      conditions.push(`date:>=${now}`);
    }
    else if (tab.value === 'past') {
      conditions.push(`date:<${now}`);
    }

    // Locale condition for international events
    if (locale.value === 'en') {
      conditions.push('is_international:=true');
    }

    // Category filter
    if (selectedCategories.value.length > 0) {
      const escaped = selectedCategories.value.map(c => `\`${c.replace(/\\/g, '\\\\').replace(/`/g, '\\`')}\``).join(',');
      conditions.push(`category_name:=[${escaped}]`);
    }

    // Tenant filter
    if (selectedTenants.value.length > 0) {
      const escaped = selectedTenants.value.map(t => `\`${t.replace(/\\/g, '\\\\').replace(/`/g, '\\`')}\``).join(',');
      conditions.push(`tenant_shortname:=[${escaped}]`);
    }

    // Remote filter
    if (isRemoteOnly.value) {
      conditions.push('is_remote:=true');
    }

    // Year filter
    if (selectedYears.value.length > 0) {
      conditions.push(`year:=[${selectedYears.value.join(',')}]`);
    }

    return conditions;
  };

  const syncUrlParams = () => {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams();

    if (tab.value !== 'upcoming') params.set('tab', tab.value);
    if (query.value.trim()) params.set('q', query.value.trim());
    if (selectedCategories.value.length > 0) {
      params.set('category', selectedCategories.value.join(','));
    }
    if (selectedTenants.value.length > 0) {
      params.set('tenant', selectedTenants.value.join(','));
    }
    if (selectedYears.value.length > 0) {
      params.set('year', selectedYears.value.join(','));
    }
    if (isRemoteOnly.value) params.set('remote', '1');
    if (sortBy.value !== 'relevance') params.set('sort', sortBy.value);

    const newQuery = params.toString();
    const newUrl = `${window.location.pathname}${newQuery ? `?${newQuery}` : ''}`;
    window.history.replaceState({}, '', newUrl);
  };

  const parseUrlParams = () => {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams(window.location.search);

    const tabParam = params.get('tab');
    if (tabParam === 'upcoming' || tabParam === 'past' || tabParam === 'all') {
      tab.value = tabParam;
    }

    // `q` matches the convention used by useDocumentSearch — the site's search pages all
    // read/write the same query-string key so a "view all" link's `?q=` term is picked up
    // regardless of which collection it points to.
    const searchParam = params.get('q');
    if (searchParam) {
      query.value = searchParam;
    }

    const categoryParam = params.get('category');
    if (categoryParam) {
      selectedCategories.value = categoryParam.split(',').filter(Boolean);
    }

    const tenantParam = params.get('tenant');
    if (tenantParam) {
      selectedTenants.value = tenantParam.split(',').filter(Boolean);
    }

    const yearParam = params.get('year');
    if (yearParam) {
      selectedYears.value = yearParam.split(',').filter(Boolean);
    }

    const remoteParam = params.get('remote');
    if (remoteParam === '1' || remoteParam === 'true') {
      isRemoteOnly.value = true;
    }

    const sortParam = params.get('sort');
    if (sortParam === 'relevance' || sortParam === 'date_asc' || sortParam === 'date_desc') {
      sortBy.value = sortParam;
    }
  };

  const updateFacetsFromResponse = (facetCounts?: Array<{ field_name: string; counts: Array<{ value: string; count: number }> }>) => {
    if (!facetCounts) return;

    for (const facet of facetCounts) {
      if (facet.field_name === 'category_name') {
        categoryFacets.value = facet.counts.map(c => ({
          label: c.value,
          value: c.value,
          count: c.count,
        }));
      }
      else if (facet.field_name === 'tenant_shortname') {
        tenantFacets.value = facet.counts.map(c => ({
          label: c.value,
          value: c.value,
          count: c.count,
        }));
      }
      else if (facet.field_name === 'year') {
        yearFacets.value = facet.counts
          .map(c => ({
            label: String(c.value),
            value: String(c.value),
            count: c.count,
          }))
          .sort((a, b) => Number(b.value) - Number(a.value));
      }
    }
  };

  const performSearch = async (isLoadMore = false) => {
    const searchClient = getClient();
    if (!searchClient) {
      error.value = 'Typesense is not configured';
      return;
    }

    if (abortController) {
      abortController.abort();
    }
    abortController = new AbortController();

    if (isLoadMore) {
      isLoadingMore.value = true;
    }
    else {
      isLoading.value = true;
      currentPage.value = 1;
    }
    error.value = null;

    const targetPage = isLoadMore ? currentPage.value + 1 : 1;
    const filterConditions = buildFilterConditions();
    const dateTiebreak = tab.value === 'upcoming' ? 'date:asc' : 'date:desc';
    const hasQuery = Boolean(query.value.trim()) && query.value.trim() !== '*';
    let sortExpression = hasQuery ? `_text_match:desc,${dateTiebreak}` : dateTiebreak;
    if (sortBy.value === 'date_asc') {
      sortExpression = 'date:asc';
    }
    else if (sortBy.value === 'date_desc') {
      sortExpression = 'date:desc';
    }

    const searchParams: Record<string, unknown> = {
      q: query.value.trim() || '*',
      query_by: 'title,title_lt,title_en,description,location',
      query_by_weights: '10,8,8,3,2',
      filter_by: filterConditions.join(' && '),
      sort_by: sortExpression,
      facet_by: 'category_name,tenant_shortname,year',
      max_facet_values: 50,
      per_page: perPage,
      page: targetPage,
      prefix: true,
      infix: 'fallback',
      prioritize_exact_match: true,
      prioritize_token_position: true,
    };

    try {
      const response = await searchClient.search(
        getCollectionName(),
        searchParams,
        abortController.signal,
      );

      const hits = (response.hits ?? []).map(h => h.document as unknown as CalendarEventDocument);
      totalHits.value = response.found ?? 0;
      currentPage.value = targetPage;

      if (isLoadMore) {
        events.value = [...events.value, ...hits];
      }
      else {
        events.value = hits;
      }

      updateFacetsFromResponse(response.facet_counts);
      syncUrlParams();
    }
    catch (err: unknown) {
      if (err instanceof Error && err.name === 'AbortError') {
        return;
      }
      error.value = err instanceof Error ? err.message : 'Search failed';
    }
    finally {
      isLoading.value = false;
      isLoadingMore.value = false;
    }
  };

  const debouncedSearch = debounce(() => {
    performSearch(false);
  }, 300);

  // Watch query with debounce
  watch(query, () => {
    debouncedSearch();
  });

  // Watch filters and tab with immediate search
  watch([tab, selectedCategories, selectedTenants, selectedYears, isRemoteOnly, sortBy], () => {
    performSearch(false);
  }, { deep: true });

  const setTab = (newTab: CalendarSearchTab) => {
    if (tab.value === newTab) return;
    tab.value = newTab;
  };

  const toggleCategory = (categoryName: string) => {
    const idx = selectedCategories.value.indexOf(categoryName);
    if (idx >= 0) {
      selectedCategories.value.splice(idx, 1);
    }
    else {
      selectedCategories.value.push(categoryName);
    }
  };

  const toggleTenant = (tenantShortname: string) => {
    const idx = selectedTenants.value.indexOf(tenantShortname);
    if (idx >= 0) {
      selectedTenants.value.splice(idx, 1);
    }
    else {
      selectedTenants.value.push(tenantShortname);
    }
  };

  const toggleYear = (year: string | number) => {
    const str = String(year);
    const idx = selectedYears.value.indexOf(str);
    if (idx >= 0) {
      selectedYears.value.splice(idx, 1);
    }
    else {
      selectedYears.value.push(str);
    }
  };

  const toggleRemote = () => {
    isRemoteOnly.value = !isRemoteOnly.value;
  };

  const setSortBy = (newSortBy: CalendarSearchSort) => {
    if (sortBy.value === newSortBy) return;
    sortBy.value = newSortBy;
  };

  const clearFilters = () => {
    query.value = '';
    selectedCategories.value = [];
    selectedTenants.value = [];
    selectedYears.value = [];
    isRemoteOnly.value = false;
  };

  const loadMore = () => {
    if (!hasMore.value || isLoadingMore.value || isLoading.value) return;
    performSearch(true);
  };

  parseUrlParams();

  if (getCurrentInstance()) {
    onMounted(() => {
      performSearch(false);
    });

    onUnmounted(() => {
      if (abortController) {
        abortController.abort();
      }
      debouncedSearch.cancel();
    });
  }

  return {
    query,
    tab,
    selectedCategories,
    selectedTenants,
    selectedYears,
    isRemoteOnly,
    sortBy,
    events,
    totalHits,
    currentPage,
    isLoading,
    isLoadingMore,
    hasMore,
    hasActiveFilters,
    activeFilterCount,
    categoryFacets,
    tenantFacets,
    yearFacets,
    error,
    setTab,
    toggleCategory,
    toggleTenant,
    toggleYear,
    toggleRemote,
    setSortBy,
    clearFilters,
    loadMore,
    performSearch,
  };
}
