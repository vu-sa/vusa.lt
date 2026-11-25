import { computed, ref } from 'vue';

import type { SearchMatchDocument } from '@/Search/core/matches';
import { FilterUtils, quoteFilterValue } from '@/Search/core/services/FilterUtils';
import { listingDateSort, useListingSearch, type FacetOption, type ListingSearchSort } from '@/Search/public/composables/useListingSearch';
import type { NewsItem } from '@/Types/contentParts';
import { imageFromSearch } from '@/Types/media';

export type { FacetOption };

export interface NewsDocument extends SearchMatchDocument, Record<string, unknown> {
  id: string | number;
  title: string;
  short?: string;
  permalink?: string | null;
  public_url?: string | null;
  image?: string;
  publish_time: number;
  lang?: string;
  tenant_id: number;
  tenant_ids?: number[];
  tenant_name?: string;
  tenant_shortname?: string;
  tag_names?: string[];
  year?: number;
  important?: boolean;
  created_at?: number;
}

export type NewsSearchSort = ListingSearchSort;

export interface UseNewsSearchOptions {
  initialNews?: NewsItem[];
  initialTotal?: number;
  initialTenant?: string;
  initialTag?: string;
  initialYear?: string;
  perPage?: number;
}

export function useNewsSearch(options: UseNewsSearchOptions = {}) {
  const perPage = options.perPage ?? 15;

  const selectedTenants = ref<string[]>(options.initialTenant ? [options.initialTenant] : []);
  const selectedYears = ref<string[]>(options.initialYear ? [options.initialYear] : []);
  const selectedTags = ref<string[]>(options.initialTag ? [options.initialTag] : []);

  const tenantFacets = ref<FacetOption[]>([]);
  const yearFacets = ref<FacetOption[]>([]);
  const tagFacets = ref<FacetOption[]>([]);

  const buildFilterConditions = (locale: string, excludeField?: string): string[] => {
    const conditions: string[] = [];

    // Locale condition
    conditions.push(`lang:=${locale}`);

    // Tenant filter
    if (excludeField !== 'tenant_shortname' && selectedTenants.value.length > 0) {
      const escaped = selectedTenants.value.map(quoteFilterValue).join(',');
      conditions.push(`tenant_shortname:=[${escaped}]`);
    }

    // Year filter
    if (excludeField !== 'year' && selectedYears.value.length > 0) {
      conditions.push(`year:=[${selectedYears.value.join(',')}]`);
    }

    // Tag filter
    if (excludeField !== 'tag_names' && selectedTags.value.length > 0) {
      const escaped = selectedTags.value.map(quoteFilterValue).join(',');
      conditions.push(`tag_names:=[${escaped}]`);
    }

    return conditions;
  };

  const documentToNewsItem = (doc: NewsDocument, locale: string): NewsItem => {
    const publishIso = doc.publish_time
      ? new Date(doc.publish_time < 10000000000 ? doc.publish_time * 1000 : doc.publish_time).toISOString()
      : new Date().toISOString();

    return {
      id: Number(doc.id),
      title: doc.title,
      _searchMatch: doc._searchMatch,
      _searchTitleMatches: doc._searchTitleMatches,
      _searchFieldMatches: doc._searchFieldMatches,
      short: doc.short ?? '',
      permalink: doc.permalink ?? null,
      public_url: doc.public_url ?? null,
      image: doc.image ?? '',
      image_media: imageFromSearch(doc),
      publish_time: publishIso,
      lang: doc.lang ?? locale,
    };
  };

  const { items: news, ...listing } = useListingSearch<NewsDocument, NewsItem>({
    collection: 'public_news',
    perPage,
    initialItems: options.initialNews?.map(item => ({ ...item })),
    initialTotal: options.initialTotal,
    filters: { tenant: selectedTenants, year: selectedYears, tag: selectedTags },
    facets: { tenant_shortname: tenantFacets, year: yearFacets, tag_names: tagFacets },
    buildFilters: buildFilterConditions,
    buildSort: (sort, hasQuery) => listingDateSort('publish_time', sort, hasQuery, 'desc'),
    queryBy: 'title,short,tenant_name,tenant_shortname,tag_names',
    queryByWeights: '10,4,2,2,3',
    mapDocument: documentToNewsItem,
  });

  const { query, sortBy } = listing;

  const hasActiveFilters = computed(() =>
    Boolean(query.value.trim())
    || selectedTenants.value.length > 0
    || selectedYears.value.length > 0
    || selectedTags.value.length > 0,
  );

  const activeFilterCount = computed(() =>
    selectedTenants.value.length
    + selectedYears.value.length
    + selectedTags.value.length,
  );

  const toggleTenant = (tenantShortname: string) => {
    selectedTenants.value = FilterUtils.toggleArrayValue(selectedTenants.value, tenantShortname);
  };

  const toggleYear = (year: string | number) => {
    selectedYears.value = FilterUtils.toggleArrayValue(selectedYears.value, String(year));
  };

  const toggleTag = (tagName: string) => {
    selectedTags.value = FilterUtils.toggleArrayValue(selectedTags.value, tagName);
  };

  const setSortBy = (newSortBy: NewsSearchSort) => {
    if (sortBy.value === newSortBy) return;
    sortBy.value = newSortBy;
  };

  const clearFilters = () => {
    query.value = '';
    selectedTenants.value = [];
    selectedYears.value = [];
    selectedTags.value = [];
  };

  return {
    ...listing,
    news,
    selectedTenants,
    selectedYears,
    selectedTags,
    hasActiveFilters,
    activeFilterCount,
    tenantFacets,
    yearFacets,
    tagFacets,
    toggleTenant,
    toggleYear,
    toggleTag,
    setSortBy,
    clearFilters,
  };
}
