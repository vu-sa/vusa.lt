import { computed, watch } from 'vue';
import { useLocalStorage } from '@vueuse/core';
import { usePage } from '@inertiajs/vue3';

import { useBaseSearch } from '@/Search/core/composables/useBaseSearch';
import { createSearchReporter } from '@/Search/core/searchReporter';
import { FilterUtils } from '@/Search/core/services/FilterUtils';
import { DocumentSearchService } from '@/Search/public/DocumentSearchService';
import { usePublicSearchService } from '@/Search/public/composables/usePublicSearchService';
import { readSearchParams, replaceSearchParams } from '@/Search/public/searchUrl';
import type { DocumentEffectStatusValue, DocumentSearchFilters, DocumentSearchSort, DocumentFacet, DocumentSearchPreferences, DocumentSearchController } from '@/Types/DocumentSearchTypes';

export const useDocumentSearch = (): DocumentSearchController => {
  const documentPreferences = useLocalStorage<DocumentSearchPreferences>('document-search-preferences', {
    viewMode: 'list',
    recentSearches: [],
  });

  // Default filters for document search. `effectStatuses: ['true', 'unknown']` hides
  // "Negalioja" documents by default — deselecting it (or clearing) reveals them.
  const defaultEffectStatuses: DocumentEffectStatusValue[] = ['true', 'unknown'];
  const defaultFilters: DocumentSearchFilters = {
    query: '',
    tenants: [],
    contentTypes: [],
    languages: [],
    dateRange: {},
    sort: 'relevance',
    effectStatuses: defaultEffectStatuses,
  };

  // Filter key mapper for document facets
  const filterKeyMapper = (field: string, filters: DocumentSearchFilters): string[] => {
    switch (field) {
      case 'tenant_shortname':
        return filters.tenants;
      case 'content_type':
        return filters.contentTypes;
      case 'language':
        return filters.languages;
      default:
        return [];
    }
  };

  const baseSearch = useBaseSearch<DocumentSearchFilters, DocumentFacet, Record<string, unknown>>({
    defaultFilters,
    searchService: null,
    filterKeyMapper,
    localStorageKey: 'document-search-state',
    // Both always hold a non-empty value at their defaults, so the generic active-filter
    // counter would otherwise flag them even when nobody has touched them.
    excludeFromFilterCount: ['sort', 'effectStatuses'],
  });

  const page = usePage<{ importantContentTypes?: string[]; hasDocumentRecommendations?: boolean }>();
  const { searchClient, initializeSearchClient } = usePublicSearchService(baseSearch, 'documents', (client, collection) =>
    new DocumentSearchService(client, collection, {
      importantContentTypes: page.props.importantContentTypes ?? [],
      recommendationsUrl: page.props.hasDocumentRecommendations ? route('api.v1.documents.recommendations') : undefined,
    }),
  );

  const toggleTenant = (tenantShortname: string) => {
    const current = baseSearch.filters.value.tenants;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      tenants: FilterUtils.toggleArrayValue(current, tenantShortname),
    };
    baseSearch.debouncedSearch();
  };

  const toggleContentType = (contentType: string) => {
    const current = baseSearch.filters.value.contentTypes;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      contentTypes: FilterUtils.toggleArrayValue(current, contentType),
    };
    baseSearch.debouncedSearch();
  };

  const toggleLanguage = (language: string) => {
    const current = baseSearch.filters.value.languages;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      languages: FilterUtils.toggleArrayValue(current, language),
    };
    baseSearch.debouncedSearch();
  };

  const setDateRange = (dateRange: { from?: Date; to?: Date; preset?: string }) => {
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      dateRange: {
        from: dateRange.from,
        to: dateRange.to,
        preset: dateRange.preset as DocumentSearchFilters['dateRange']['preset'],
      },
    };
    baseSearch.debouncedSearch();
  };

  const hasQuery = computed(() => {
    const query = baseSearch.filters.value.query.trim();
    return query !== '' && query !== '*';
  });

  // 'relevance' is the automatic default: newest first while browsing, relevance once a query exists.
  const activeSort = computed<DocumentSearchSort>(() => {
    const sort = baseSearch.filters.value.sort ?? 'relevance';
    return sort === 'relevance' && !hasQuery.value ? 'date_desc' : sort;
  });

  const setSortBy = (choice: DocumentSearchSort) => {
    // Picking the browsing default must not opt out of switching to relevance on the next query.
    const sort = choice === 'date_desc' && !hasQuery.value ? 'relevance' : choice;
    if (baseSearch.filters.value.sort === sort) return;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      sort,
    };
    baseSearch.debouncedSearch();
  };

  const toggleEffectStatus = (value: DocumentEffectStatusValue) => {
    const current = baseSearch.filters.value.effectStatuses ?? defaultEffectStatuses;
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      effectStatuses: FilterUtils.toggleArrayValue(current, value),
    };
    baseSearch.debouncedSearch();
  };

  const clearEffectStatuses = () => {
    baseSearch.filters.value = {
      ...baseSearch.filters.value,
      effectStatuses: [],
    };
    baseSearch.debouncedSearch();
  };

  const setViewMode = (mode: 'list' | 'compact') => {
    documentPreferences.value.viewMode = mode;
  };

  const clearFilters = () => {
    baseSearch.filters.value = {
      query: baseSearch.filters.value.query,
      tenants: [],
      contentTypes: [],
      languages: [],
      dateRange: {},
      sort: 'relevance',
      // [] means no restriction — same convention as every other array filter here,
      // so "clear all" also reveals "Negalioja" documents.
      effectStatuses: [],
    };
    baseSearch.debouncedSearch();
  };

  const clearRecentSearches = () => {
    documentPreferences.value.recentSearches = [];
  };

  const removeRecentSearch = (searchToRemove: string) => {
    documentPreferences.value.recentSearches = documentPreferences.value.recentSearches.filter(
      s => s !== searchToRemove,
    );
  };

  const searchState = computed(() => ({
    status: baseSearch.status.value,
    isSearching: baseSearch.status.value === 'searching',
    hasResults: baseSearch.hasResults.value,
    totalHits: baseSearch.totalHits.value,
    query: baseSearch.displayQuery.value,
    filters: baseSearch.filters.value,
    facets: baseSearch.facets.value,
    results: baseSearch.results.value,
    viewMode: documentPreferences.value.viewMode,
    error: baseSearch.error.value,
    isOnline: baseSearch.isOnline.value,
  }));

  /**
   * Sync current filter state to URL using array notation for multi-value params
   * Following HTML standard: tenants[]=value1&tenants[]=value2
   */
  const syncFiltersToUrl = () => {
    const filters = baseSearch.filters.value;

    const params = new URLSearchParams();

    // Query
    if (filters.query && filters.query !== '' && filters.query !== '*') {
      params.set('q', filters.query);
    }

    // Tenants (array notation)
    filters.tenants?.forEach((tenant) => {
      params.append('tenants[]', tenant);
    });

    // Content types (array notation)
    filters.contentTypes?.forEach((type) => {
      params.append('contentTypes[]', type);
    });

    // Languages (array notation)
    filters.languages?.forEach((lang) => {
      params.append('language[]', lang);
    });

    // Date range
    if (filters.dateRange?.from) {
      params.set('dateFrom', Math.floor(filters.dateRange.from.getTime() / 1000).toString());
    }
    if (filters.dateRange?.to) {
      params.set('dateTo', Math.floor(filters.dateRange.to.getTime() / 1000).toString());
    }
    if (filters.dateRange?.preset && filters.dateRange.preset !== 'recent') {
      params.set('datePreset', filters.dateRange.preset);
    }

    // Only synced when non-default, to keep the URL clean for the common case.
    if (filters.sort && filters.sort !== 'relevance') {
      params.set('sort', filters.sort);
    }
    const effectStatuses = filters.effectStatuses ?? defaultEffectStatuses;
    const isDefaultEffectStatuses = effectStatuses.length === defaultEffectStatuses.length
      && defaultEffectStatuses.every(s => effectStatuses.includes(s));
    if (!isDefaultEffectStatuses) {
      if (effectStatuses.length === 0) {
        // Explicit marker for "no restriction" — an empty array wouldn't otherwise
        // round-trip through the URL (no params to append).
        params.set('status', 'all');
      }
      else {
        effectStatuses.forEach((status) => {
          params.append('status[]', status);
        });
      }
    }

    replaceSearchParams(params, true);
  };

  /**
   * Load filter state from URL
   */
  const loadFiltersFromUrl = (): Partial<DocumentSearchFilters> => {
    const params = readSearchParams();
    const filters: Partial<DocumentSearchFilters> = {};

    // Query
    const query = params.get('q');
    if (query) {
      filters.query = query;
    }

    // Parse array params using HTML standard notation (param[]=value)
    const parseArrayParam = (paramName: string): string[] => {
      const values = params.getAll(`${paramName}[]`);
      // Also support indexed notation for backwards compatibility
      if (values.length === 0) {
        const indexed: string[] = [];
        for (let i = 0; i < 50; i++) {
          const value = params.get(`${paramName}[${i}]`);
          if (value) {
            indexed.push(value);
          }
        }
        return indexed;
      }
      return values;
    };

    const tenants = parseArrayParam('tenants');
    if (tenants.length > 0) filters.tenants = tenants;

    const contentTypes = parseArrayParam('contentTypes');
    if (contentTypes.length > 0) filters.contentTypes = contentTypes;

    const languages = parseArrayParam('language');
    if (languages.length > 0) filters.languages = languages;

    // Date range
    const dateFrom = params.get('dateFrom');
    const dateTo = params.get('dateTo');
    const datePreset = params.get('datePreset');

    if (dateFrom || dateTo || datePreset) {
      filters.dateRange = {
        from: dateFrom ? new Date(Number(dateFrom) * 1000) : undefined,
        to: dateTo ? new Date(Number(dateTo) * 1000) : undefined,
        preset: datePreset as DocumentSearchFilters['dateRange']['preset'],
      };
    }

    const sortParam = params.get('sort');
    if (sortParam === 'date_asc' || sortParam === 'date_desc' || sortParam === 'relevance') {
      filters.sort = sortParam;
    }

    if (params.get('status') === 'all') {
      filters.effectStatuses = [];
    }
    else {
      const statuses = parseArrayParam('status').filter(
        (s): s is DocumentEffectStatusValue => s === 'true' || s === 'false' || s === 'unknown',
      );
      if (statuses.length > 0) filters.effectStatuses = statuses;
    }

    return filters;
  };

  // Flag to prevent URL sync during initial load
  let isInitialLoad = true;

  // Watch for filter changes and sync to URL (after initial load)
  watch(
    () => baseSearch.filters.value,
    () => {
      if (!isInitialLoad && baseSearch.status.value !== 'searching') {
        syncFiltersToUrl();
      }
    },
    { deep: true },
  );

  const searchReporter = createSearchReporter('documents');

  // Also sync after search completes
  watch(
    () => baseSearch.status.value,
    (newStatus, oldStatus) => {
      if (oldStatus !== 'searching' || newStatus !== 'idle') {
        return;
      }

      if (!isInitialLoad) {
        syncFiltersToUrl();
      }

      const query = baseSearch.filters.value.query.trim();
      if (query === '' || query === '*') {
        searchReporter.reset();
      }
      else if (!baseSearch.error.value) {
        searchReporter.report(query, baseSearch.totalHits.value);
      }
    },
  );

  // Wrap initializeSearchClient to load URL params first
  const initializeWithUrlSync = async () => {
    Object.assign(baseSearch.filters.value, loadFiltersFromUrl());

    // Initialize search client
    const result = await initializeSearchClient();

    // Mark initial load as complete after a short delay
    setTimeout(() => {
      isInitialLoad = false;
    }, 500);

    return result;
  };

  return {
    // State from base
    searchState,
    isSearching: baseSearch.isSearching,
    isLoadingFacets: baseSearch.isLoadingFacets,
    isLoadingMore: baseSearch.isLoadingMore,
    hasResults: baseSearch.hasResults,
    hasActiveFilters: baseSearch.hasActiveFilters,
    hasMoreResults: baseSearch.hasMoreResults,
    totalHits: baseSearch.totalHits,
    results: baseSearch.results,
    facets: baseSearch.mergedFacets, // Use merged facets
    filters: baseSearch.filters,
    hasQuery,
    activeSort,
    viewMode: computed(() => documentPreferences.value.viewMode),
    recentSearches: computed(() => documentPreferences.value.recentSearches),

    // Error handling
    searchError: baseSearch.error,
    isOnline: baseSearch.isOnline,
    retryCount: baseSearch.retryCount,
    maxRetries: baseSearch.maxRetries,

    // Actions from base
    search: baseSearch.search,
    setFilter: baseSearch.setFilter,
    loadMore: baseSearch.loadMore,
    retrySearch: baseSearch.retrySearch,
    clearError: baseSearch.clearError,
    loadInitialFacets: baseSearch.loadInitialFacets,

    // Document-specific actions
    toggleTenant,
    toggleContentType,
    toggleLanguage,
    setDateRange,
    setSortBy,
    toggleEffectStatus,
    clearEffectStatuses,
    setViewMode,
    clearFilters,
    clearRecentSearches,
    removeRecentSearch,

    // Internal
    searchClient,
    initializeSearchClient: initializeWithUrlSync,
    cancelPendingSearch: baseSearch.cancelPendingSearch,
  };
};
