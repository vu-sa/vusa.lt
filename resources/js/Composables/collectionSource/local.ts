import { computed, isRef, ref, toValue, type MaybeRefOrGetter, type Ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useCollectionFilterMemory } from '@/Composables/collectionFilterMemory';

import type { CollectionFacet, CollectionSortOption, CollectionChip, CollectionSource } from './types';
import { PAGE_SIZE, useOptimisticOverlay } from './shared';

interface LocalFacetDefinition<T> {
  field: string;
  label: string;
  /** The value(s) an item carries for this facet. */
  get: (item: T) => string | string[] | null | undefined;
  /** Readable label per value; defaults to the value itself. */
  valueLabel?: (value: string) => string;
}

interface LocalSortOption<T> extends CollectionSortOption {
  /** Plain value to compare; strings compare with the Lithuanian collator. */
  by: (item: T) => string | number | null | undefined;
}

interface LocalCollectionSourceOptions<T> {
  items: Ref<readonly T[]> | readonly T[];
  /** The text a query matches against, one or more strings per item. */
  searchText: (item: T) => (string | null | undefined)[];
  /** A getter, so labels built with `$t()` follow translations that load after the page mounts. */
  sortOptions: MaybeRefOrGetter<LocalSortOption<T>[]>;
  defaultSort: string;
  facets?: LocalFacetDefinition<T>[];
}

const collator = new Intl.Collator('lt', { sensitivity: 'base', numeric: true });

function normalise(value: string): string {
  return value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();
}

/**
 * For small lists sent whole as an Inertia prop (roles, types, tenants): search, facets with
 * counts, sort and "Rodyti daugiau" all run in the browser, so the list needs no API endpoint
 * and still looks and behaves like every other collection.
 */
export function useLocalCollectionSource<T>(options: LocalCollectionSourceOptions<T>): CollectionSource<T> {
  const filterMemory = useCollectionFilterMemory([...(options.facets ?? []).map(facet => facet.field), 'sort']);
  filterMemory.restore();
  const initialParams = new URLSearchParams(window.location.search);
  const source = computed<readonly T[]>(() => (isRef(options.items) ? options.items.value : options.items));
  const facetDefinitions = options.facets ?? [];

  const query = ref(initialParams.get('search') ?? '');
  const sortBy = ref(toValue(options.sortOptions).some(option => option.value === initialParams.get('sort'))
    ? initialParams.get('sort') as string
    : options.defaultSort);
  const visible = ref(PAGE_SIZE * Math.max(1, Number(initialParams.get('pages')) || 1));
  const filters = ref<Record<string, unknown>>(
    Object.fromEntries(facetDefinitions
      .map(facet => [facet.field, initialParams.get(facet.field)?.split(',').filter(Boolean) ?? []] as const)
      .filter(([, values]) => values.length > 0)),
  );

  const valuesOf = (facet: LocalFacetDefinition<T>, item: T): string[] => {
    const raw = facet.get(item);

    return (Array.isArray(raw) ? raw : raw === null || raw === undefined || raw === '' ? [] : [raw]).map(String);
  };
  const selectedValues = (field: string): string[] => (filters.value[field] as string[] | undefined) ?? [];

  const matchesQuery = (item: T): boolean => {
    const needle = normalise(query.value.trim());

    return needle === '' || options.searchText(item).some(text => text && normalise(text).includes(needle));
  };

  const matchesFacets = (item: T, except?: string): boolean =>
    facetDefinitions.every((facet) => {
      const selected = selectedValues(facet.field);

      return facet.field === except || selected.length === 0 || valuesOf(facet, item).some(value => selected.includes(value));
    });

  const filtered = computed(() => {
    const sortOptions = toValue(options.sortOptions);
    const sort = sortOptions.find(option => option.value === sortBy.value) ?? sortOptions[0];
    const direction = sort?.value.endsWith(':desc') ? -1 : 1;
    const rows = source.value.filter(item => matchesQuery(item) && matchesFacets(item));

    if (!sort) {
      return rows;
    }

    return [...rows].sort((left, right) => {
      const a = sort.by(left);
      const b = sort.by(right);

      if (a === b) {
        return 0;
      }
      if (a === null || a === undefined) {
        return 1;
      }
      if (b === null || b === undefined) {
        return -1;
      }

      return direction * (typeof a === 'number' && typeof b === 'number' ? a - b : collator.compare(String(a), String(b)));
    });
  });

  // Counts answer "what would I get if I picked this too", so each facet ignores its own selection.
  const facets = computed<CollectionFacet[]>(() =>
    facetDefinitions.map((facet) => {
      const counts = new Map<string, number>();

      for (const item of source.value) {
        if (!matchesQuery(item) || !matchesFacets(item, facet.field)) {
          continue;
        }
        for (const value of valuesOf(facet, item)) {
          counts.set(value, (counts.get(value) ?? 0) + 1);
        }
      }

      const all = new Set([...counts.keys(), ...selectedValues(facet.field)]);

      return {
        field: facet.field,
        label: facet.label,
        type: 'checkbox' as const,
        values: [...all]
          .map(value => ({
            value,
            label: facet.valueLabel?.(value) ?? value,
            count: counts.get(value) ?? 0,
            isSelected: selectedValues(facet.field).includes(value),
          }))
          .sort((left, right) => collator.compare(left.label, right.label)),
      };
    }),
  );

  const chips = computed<CollectionChip[]>(() =>
    facetDefinitions.flatMap(facet =>
      selectedValues(facet.field).map(value => ({
        id: `${facet.field}:${value}`,
        label: `${facet.label}: ${facet.valueLabel?.(value) ?? value}`,
      })),
    ),
  );

  function syncUrl(): void {
    const url = new URL(window.location.href);

    for (const key of ['search', 'sort', 'pages', ...facetDefinitions.map(facet => facet.field)]) {
      url.searchParams.delete(key);
    }
    if (query.value.trim()) {
      url.searchParams.set('search', query.value.trim());
    }
    if (sortBy.value !== options.defaultSort) {
      url.searchParams.set('sort', sortBy.value);
    }
    if (visible.value > PAGE_SIZE) {
      url.searchParams.set('pages', String(Math.ceil(visible.value / PAGE_SIZE)));
    }
    for (const facet of facetDefinitions) {
      if (selectedValues(facet.field).length > 0) {
        url.searchParams.set(facet.field, selectedValues(facet.field).join(','));
      }
    }

    window.history.replaceState(window.history.state, '', url.toString());
    filterMemory.remember();
  }

  function setFilter(field: string, value: unknown): void {
    const next = { ...filters.value };
    const values = (Array.isArray(value) ? value : value === undefined || value === '' ? [] : [value]).map(String);

    if (values.length === 0) {
      delete next[field];
    }
    else {
      next[field] = values;
    }

    filters.value = next;
    visible.value = PAGE_SIZE;
    syncUrl();
  }

  function toggleFilter(field: string, value: string): void {
    const current = selectedValues(field);

    setFilter(field, current.includes(value) ? current.filter(selected => selected !== value) : [...current, value]);
  }

  const overlay = useOptimisticOverlay<T>();
  const items = computed(() => overlay.apply(filtered.value.slice(0, visible.value)));

  return {
    items,
    total: computed(() => filtered.value.length),
    isLoading: ref(false),
    isLoadingMore: ref(false),
    hasMore: computed(() => filtered.value.length > visible.value),
    hasSearched: ref(true),
    error: ref<string | null>(null),
    query,
    filters,
    facets,
    chips,
    activeFilterCount: computed(() => chips.value.length),
    sortBy,
    sortOptions: computed(() => toValue(options.sortOptions).map(({ value, label }) => ({ value, label }))),
    search: (next) => {
      query.value = next;
      visible.value = PAGE_SIZE;
      syncUrl();
    },
    toggleFilter,
    setFilter,
    clearFilters: () => {
      filters.value = {};
      syncUrl();
    },
    clearChip: (id) => {
      const [field, ...rest] = id.split(':');
      toggleFilter(field, rest.join(':'));
    },
    // Local rows are the prop itself; an optimistic change arrives with the next Inertia reload.
    replaceItems: () => undefined,
    patchItems: overlay.patchItems,
    hideItems: overlay.hideItems,
    setSortBy: (next) => {
      sortBy.value = next;
      syncUrl();
    },
    loadMore: () => {
      visible.value += PAGE_SIZE;
      syncUrl();
    },
    refresh: () => undefined,
  };
}
