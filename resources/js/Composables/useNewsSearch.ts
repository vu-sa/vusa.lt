import { ref, computed, watch, onMounted, onUnmounted, getCurrentInstance } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';

import { SearchClientFactory, type TypesenseClient } from '@/Shared/Search/services/SearchClientFactory';
import type { NewsItem } from '@/Types/contentParts';

export interface NewsDocument {
  id: string | number;
  title: string;
  short?: string;
  permalink?: string | null;
  image?: string;
  publish_time: number;
  lang?: string;
  tenant_id: number;
  tenant_ids?: number[];
  tenant_name?: string;
  tenant_shortname?: string;
  category_id?: number | null;
  category_name?: string | null;
  tag_names?: string[];
  year?: number;
  important?: boolean;
  created_at?: number;
}

export interface FacetOption {
  label: string;
  value: string;
  count: number;
}

export type NewsSearchSort = 'relevance' | 'date_desc' | 'date_asc';

export interface UseNewsSearchOptions {
  initialNews?: NewsItem[];
  initialTotal?: number;
  initialCategory?: string;
  initialTenant?: string;
  initialTag?: string;
  initialYear?: string;
  perPage?: number;
}

interface TypesensePageConfig {
  apiKey: string;
  nodes: Array<{ protocol: string; host: string; port: number }>;
  collections?: Record<string, string>;
}

export function useNewsSearch(options: UseNewsSearchOptions = {}) {
  const page = usePage();
  const perPage = options.perPage ?? 15;

  const query = ref('');
  const selectedCategories = ref<string[]>(options.initialCategory ? [options.initialCategory] : []);
  const selectedTenants = ref<string[]>(options.initialTenant ? [options.initialTenant] : []);
  const selectedYears = ref<string[]>(options.initialYear ? [options.initialYear] : []);
  const selectedTags = ref<string[]>(options.initialTag ? [options.initialTag] : []);
  const sortBy = ref<NewsSearchSort>('relevance');

  const news = ref<NewsItem[]>((options.initialNews ?? []).map(item => ({ ...item })));
  const totalHits = ref(options.initialTotal ?? options.initialNews?.length ?? 0);
  const currentPage = ref(1);
  const isLoading = ref(false);
  const isLoadingMore = ref(false);
  const error = ref<string | null>(null);

  // Facets
  const categoryFacets = ref<FacetOption[]>([]);
  const tenantFacets = ref<FacetOption[]>([]);
  const yearFacets = ref<FacetOption[]>([]);
  const tagFacets = ref<FacetOption[]>([]);

  let abortController: AbortController | null = null;
  let client: TypesenseClient | null = null;

  const locale = computed(() => (page.props.app as { locale?: string })?.locale || 'lt');
  const hasMore = computed(() => news.value.length < totalHits.value);
  const hasActiveFilters = computed(() =>
    Boolean(query.value.trim())
    || selectedCategories.value.length > 0
    || selectedTenants.value.length > 0
    || selectedYears.value.length > 0
    || selectedTags.value.length > 0,
  );

  const activeFilterCount = computed(() =>
    selectedCategories.value.length
    + selectedTenants.value.length
    + selectedYears.value.length
    + selectedTags.value.length,
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
    return config?.collections?.public_news || 'public_news';
  };

  const buildFilterConditions = (): string[] => {
    const conditions: string[] = [];

    // Locale condition
    conditions.push(`lang:=${locale.value}`);

    // Category filter
    if (selectedCategories.value.length > 0) {
      const escaped = selectedCategories.value.map(c => `\`${c.replace(/`/g, '\\`')}\``).join(',');
      conditions.push(`category_name:=[${escaped}]`);
    }

    // Tenant filter
    if (selectedTenants.value.length > 0) {
      const escaped = selectedTenants.value.map(t => `\`${t.replace(/`/g, '\\`')}\``).join(',');
      conditions.push(`tenant_shortname:=[${escaped}]`);
    }

    // Year filter
    if (selectedYears.value.length > 0) {
      conditions.push(`year:=[${selectedYears.value.join(',')}]`);
    }

    // Tag filter
    if (selectedTags.value.length > 0) {
      const escaped = selectedTags.value.map(t => `\`${t.replace(/`/g, '\\`')}\``).join(',');
      conditions.push(`tag_names:=[${escaped}]`);
    }

    return conditions;
  };

  const syncUrlParams = () => {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams();

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
    if (selectedTags.value.length > 0) {
      params.set('tag', selectedTags.value.join(','));
    }
    if (sortBy.value !== 'relevance') params.set('sort', sortBy.value);

    const newQuery = params.toString();
    const newUrl = `${window.location.pathname}${newQuery ? `?${newQuery}` : ''}`;
    window.history.replaceState({}, '', newUrl);
  };

  const parseUrlParams = () => {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams(window.location.search);

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

    const tagParam = params.get('tag');
    if (tagParam) {
      selectedTags.value = tagParam.split(',').filter(Boolean);
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
      else if (facet.field_name === 'tag_names') {
        tagFacets.value = facet.counts.map(c => ({
          label: c.value,
          value: c.value,
          count: c.count,
        }));
      }
    }
  };

  const documentToNewsItem = (doc: NewsDocument): NewsItem => {
    const publishIso = doc.publish_time
      ? new Date(doc.publish_time < 10000000000 ? doc.publish_time * 1000 : doc.publish_time).toISOString()
      : new Date().toISOString();

    return {
      id: Number(doc.id),
      title: doc.title,
      short: doc.short ?? '',
      permalink: doc.permalink ?? null,
      image: doc.image ?? '',
      publish_time: publishIso,
      lang: doc.lang ?? locale.value,
      category: doc.category_name ?? null,
    };
  };

  const performSearch = async (isLoadMore = false) => {
    const searchClient = getClient();
    if (!searchClient) {
      // If Typesense is not configured, we keep initial SSR data
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
    const hasQuery = Boolean(query.value.trim()) && query.value.trim() !== '*';
    let sortExpression = hasQuery ? '_text_match:desc,publish_time:desc' : 'publish_time:desc';
    if (sortBy.value === 'date_asc') {
      sortExpression = 'publish_time:asc';
    }
    else if (sortBy.value === 'date_desc') {
      sortExpression = 'publish_time:desc';
    }

    const searchParams: Record<string, unknown> = {
      q: query.value.trim() || '*',
      query_by: 'title,short,tenant_name,tenant_shortname,category_name,tag_names',
      query_by_weights: '10,4,2,2,3,3',
      filter_by: filterConditions.join(' && '),
      sort_by: sortExpression,
      facet_by: 'category_name,tenant_shortname,year,tag_names',
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

      const hits = (response.hits ?? []).map(h => documentToNewsItem(h.document as unknown as NewsDocument));
      totalHits.value = response.found ?? 0;
      currentPage.value = targetPage;

      if (isLoadMore) {
        news.value = [...news.value, ...hits];
      }
      else {
        news.value = hits;
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

  // Watch filters and sort with immediate search
  watch([selectedCategories, selectedTenants, selectedYears, selectedTags, sortBy], () => {
    performSearch(false);
  }, { deep: true });

  const toggleCategory = (categoryName: string) => {
    const idx = selectedCategories.value.indexOf(categoryName);
    if (idx >= 0) {
      selectedCategories.value.splice(idx, 1);
    }
    else {
      selectedCategories.value.push(categoryName);
    }
  };

  const setCategory = (categoryName: string | null) => {
    if (!categoryName) {
      selectedCategories.value = [];
    }
    else {
      selectedCategories.value = [categoryName];
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

  const toggleTag = (tagName: string) => {
    const idx = selectedTags.value.indexOf(tagName);
    if (idx >= 0) {
      selectedTags.value.splice(idx, 1);
    }
    else {
      selectedTags.value.push(tagName);
    }
  };

  const setSortBy = (newSortBy: NewsSearchSort) => {
    if (sortBy.value === newSortBy) return;
    sortBy.value = newSortBy;
  };

  const clearFilters = () => {
    query.value = '';
    selectedCategories.value = [];
    selectedTenants.value = [];
    selectedYears.value = [];
    selectedTags.value = [];
  };

  const loadMore = () => {
    if (!hasMore.value || isLoadingMore.value || isLoading.value) return;
    performSearch(true);
  };

  parseUrlParams();

  if (getCurrentInstance()) {
    onMounted(() => {
      // If we have URL parameters or no initial items, perform initial search
      const hasUrlParams = Boolean(
        query.value.trim()
        || selectedCategories.value.length > 0
        || selectedTenants.value.length > 0
        || selectedYears.value.length > 0
        || selectedTags.value.length > 0
        || sortBy.value !== 'relevance',
      );

      if (hasUrlParams || news.value.length === 0) {
        performSearch(false);
      }
      else {
        // Just fetch facets in background while showing initial server data
        performSearch(false);
      }
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
    selectedCategories,
    selectedTenants,
    selectedYears,
    selectedTags,
    sortBy,
    news,
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
    tagFacets,
    error,
    toggleCategory,
    setCategory,
    toggleTenant,
    toggleYear,
    toggleTag,
    setSortBy,
    clearFilters,
    loadMore,
    performSearch,
  };
}
