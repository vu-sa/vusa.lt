import { computed, ref, watch, type MaybeRefOrGetter, type Ref } from 'vue';

import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import { getFacetValueLabel } from '@/Features/Admin/AdminSearch/Config/collectionFacetConfig';
import type { AdminCollection } from '@/Features/Admin/AdminSearch/Types/AdminSearchTypes';

import type { CollectionFacet, CollectionChip, TypesenseCollectionSource } from './types';
import { PAGE_SIZE, useOptimisticOverlay } from './shared';

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
  /** An always-on Typesense filter the page owns, e.g. a quick filter over per-user state. */
  baseFilterBy?: MaybeRefOrGetter<string | undefined>;
  /** Clearable filters a visit without URL filters starts with, e.g. the user's padaliniai. */
  defaultFilters?: Record<string, string[]>;
  /** Off for a collection embedded in another page, whose URL it must not rewrite. */
  syncToUrl?: boolean;
  /** Offers only these facets, e.g. not the institution's type inside that institution's record. */
  facetFields?: string[];
}

export function useTypesenseCollectionSource<T = unknown>(options: TypesenseSourceOptions): TypesenseCollectionSource<T> {
  const controller = useAdminCollectionSearch({
    collection: options.collection,
    loadFacetsOnMount: true,
    searchOnMount: true,
    syncToUrl: options.syncToUrl ?? true,
    preserveUrlKeys: options.preserveUrlKeys,
    perPage: options.perPage ?? PAGE_SIZE,
    baseFilterBy: options.baseFilterBy,
    defaultFilters: options.defaultFilters,
  });

  const labelOf = (field: string, value: string) => options.valueLabel?.(field, value) ?? getFacetValueLabel(field, value);
  const overlay = useOptimisticOverlay<T>();

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
      .filter(facet => !options.facetFields || options.facetFields.includes(facet.field))
      .map(facet => ({
        field: facet.field,
        remote: true,
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
    accessScope: computed(() => ({
      isSuperAdmin: controller.adminSearch.isSuperAdmin.value,
      tenantIds: controller.adminSearch.getCollectionTenantIds(options.collection),
      institutionIds: controller.adminSearch.getCollectionInstitutionIds(options.collection),
    })),
    items: computed(() => overlay.apply(controller.results.value as T[])),
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
    patchItems: overlay.patchItems,
    hideItems: overlay.hideItems,
    setSortBy: controller.setSortBy,
    loadMore: () => void controller.loadMore(),
    refresh: () => void controller.refresh(),
  };
}
