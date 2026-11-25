import { computed, ref } from 'vue';

import type { SearchMatchDocument } from '@/Search/core/matches';
import { FilterUtils, quoteFilterValue } from '@/Search/core/services/FilterUtils';
import { listingDateSort, useListingSearch, type FacetOption, type ListingSearchSort } from '@/Search/public/composables/useListingSearch';

export type { FacetOption };

export interface CalendarEventDocument extends SearchMatchDocument {
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
  event_type_id?: number | null;
  event_type_name?: string | null;
  location?: string | null;
  is_all_day?: boolean;
  is_remote?: boolean;
  is_international?: boolean;
  main_image_url?: string | null;
  facebook_url?: string | null;
  cto_url?: string | null;
  public_url?: string | null;
  created_at?: number;
}

export interface UseCalendarSearchOptions {
  initialTab?: CalendarSearchTab;
  perPage?: number;
}

export type CalendarSearchTab = 'upcoming' | 'past' | 'all';
export type CalendarSearchSort = ListingSearchSort;

export function useCalendarSearch(options: UseCalendarSearchOptions = {}) {
  const perPage = options.perPage ?? 18;

  const tab = ref<CalendarSearchTab>(options.initialTab ?? 'upcoming');
  const selectedEventTypes = ref<string[]>([]);
  const selectedTenants = ref<string[]>([]);
  const selectedYears = ref<string[]>([]);
  const isRemoteOnly = ref(false);

  const eventTypeFacets = ref<FacetOption[]>([]);
  const tenantFacets = ref<FacetOption[]>([]);
  const yearFacets = ref<FacetOption[]>([]);

  const buildFilterConditions = (locale: string, excludeField?: string): string[] => {
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
    if (locale === 'en') {
      conditions.push('is_international:=true');
    }

    // Event type filter
    if (excludeField !== 'event_type_name' && selectedEventTypes.value.length > 0) {
      const escaped = selectedEventTypes.value.map(quoteFilterValue).join(',');
      conditions.push(`event_type_name:=[${escaped}]`);
    }

    // Tenant filter
    if (excludeField !== 'tenant_shortname' && selectedTenants.value.length > 0) {
      const escaped = selectedTenants.value.map(quoteFilterValue).join(',');
      conditions.push(`tenant_shortname:=[${escaped}]`);
    }

    // Remote filter
    if (isRemoteOnly.value) {
      conditions.push('is_remote:=true');
    }

    // Year filter
    if (excludeField !== 'year' && selectedYears.value.length > 0) {
      conditions.push(`year:=[${selectedYears.value.join(',')}]`);
    }

    return conditions;
  };

  const { items: events, ...listing } = useListingSearch<CalendarEventDocument, CalendarEventDocument>({
    collection: 'calendar',
    perPage,
    unavailableMessage: 'Typesense is not configured',
    filters: { type: selectedEventTypes, tenant: selectedTenants, year: selectedYears },
    additionalFilterSources: [tab, isRemoteOnly],
    facets: { event_type_name: eventTypeFacets, tenant_shortname: tenantFacets, year: yearFacets },
    readUrl: (params) => {
      const value = params.get('tab');
      if (value === 'upcoming' || value === 'past' || value === 'all') tab.value = value;
      const remote = params.get('remote');
      isRemoteOnly.value = remote === '1' || remote === 'true';
    },
    writeUrl: (params) => {
      if (tab.value !== 'upcoming') params.set('tab', tab.value);
      if (isRemoteOnly.value) params.set('remote', '1');
    },
    buildFilters: buildFilterConditions,
    buildSort: (sort, hasQuery) => listingDateSort('date', sort, hasQuery, tab.value === 'upcoming' ? 'asc' : 'desc'),
    queryBy: 'title,title_lt,title_en,description,location',
    queryByWeights: '10,8,8,3,2',
    mapDocument: document => document,
  });

  const { query, sortBy } = listing;

  const hasActiveFilters = computed(() =>
    Boolean(query.value.trim())
    || selectedEventTypes.value.length > 0
    || selectedTenants.value.length > 0
    || selectedYears.value.length > 0
    || isRemoteOnly.value,
  );
  const activeFilterCount = computed(() =>
    selectedEventTypes.value.length
    + selectedTenants.value.length
    + selectedYears.value.length
    + (isRemoteOnly.value ? 1 : 0),
  );

  const setTab = (newTab: CalendarSearchTab) => {
    if (tab.value === newTab) return;
    tab.value = newTab;
  };

  const toggleEventType = (eventTypeName: string) => {
    selectedEventTypes.value = FilterUtils.toggleArrayValue(selectedEventTypes.value, eventTypeName);
  };

  const toggleTenant = (tenantShortname: string) => {
    selectedTenants.value = FilterUtils.toggleArrayValue(selectedTenants.value, tenantShortname);
  };

  const toggleYear = (year: string | number) => {
    selectedYears.value = FilterUtils.toggleArrayValue(selectedYears.value, String(year));
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
    selectedEventTypes.value = [];
    selectedTenants.value = [];
    selectedYears.value = [];
    isRemoteOnly.value = false;
  };

  return {
    ...listing,
    events,
    tab,
    selectedEventTypes,
    selectedTenants,
    selectedYears,
    isRemoteOnly,
    hasActiveFilters,
    activeFilterCount,
    eventTypeFacets,
    tenantFacets,
    yearFacets,
    setTab,
    toggleEventType,
    toggleTenant,
    toggleYear,
    toggleRemote,
    setSortBy,
    clearFilters,
  };
}
