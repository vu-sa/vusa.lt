import { computed, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useTypesenseCollectionSource } from '../useCollectionSource';

const controller = vi.hoisted(() => ({ current: null as unknown }));

vi.mock('@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch', () => ({
  useAdminCollectionSearch: () => controller.current,
}));

const facetConfig = {
  facetBy: 'year,completion_status,institution_ids',
  queryBy: 'title',
  defaultSortBy: 'start_time:desc',
  fields: [
    { field: 'year', label: 'Metai', type: 'year-pills' },
    { field: 'completion_status', label: 'Būsena', type: 'checkbox' },
    { field: 'institution_ids', label: 'Mano institucijos', type: 'checkbox' },
  ],
};

function makeController(filters: Record<string, unknown> = { query: '' }) {
  return {
    facetConfig,
    status: ref('idle'),
    error: ref(null as null | { userMessage: string }),
    filters: ref(filters),
    query: ref(''),
    results: ref([{ id: 'a' }]),
    totalHits: ref(1),
    isSearching: ref(false),
    isLoadingMore: ref(false),
    hasMoreResults: ref(false),
    facets: computed(() => [
      { field: 'completion_status', label: 'Būsena', type: 'checkbox', values: [{ value: 'incomplete', count: 3, isSelected: true }] },
      { field: 'date-range', label: 'x', type: 'date-range', values: [] },
    ]),
    sortBy: ref('start_time:desc'),
    sortOptions: computed(() => [{ value: 'start_time:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' }]),
    adminSearch: { getDirectInstitutionIds: () => ['i1', 'i2'] },
    search: vi.fn(),
    setFilter: vi.fn(),
    toggleFilter: vi.fn(),
    clearFilters: vi.fn(),
    setSortBy: vi.fn(),
    loadMore: vi.fn(),
    refresh: vi.fn(),
  };
}

const build = (filters?: Record<string, unknown>) => {
  const fake = makeController(filters);
  controller.current = fake;

  return { fake, source: useTypesenseCollectionSource({ collection: 'meetings', collapsedChips: { institution_ids: 'Mano institucijos' } }) };
};

beforeEach(() => {
  controller.current = null;
});

describe('useTypesenseCollectionSource', () => {
  it('turns each active filter value into one chip, labelled with its field and the readable value', () => {
    const { source } = build({ query: '', year: [2026], completion_status: ['incomplete'] });

    expect(source.chips.value).toEqual([
      { id: 'year:2026', label: 'Metai: 2026' },
      { id: 'completion_status:incomplete', label: 'Būsena: Neužpildyta' },
    ]);
    expect(source.activeFilterCount.value).toBe(2);
  });

  it('collapses an id list into a single chip so "3 institutions" does not read as three filters', () => {
    const { source } = build({ query: '', institution_ids: ['i1', 'i2', 'i3'] });

    expect(source.chips.value).toEqual([{ id: 'institution_ids', label: 'Mano institucijos' }]);
    expect(source.activeFilterCount.value).toBe(1);
  });

  it('counts no filters for a bare text query', () => {
    expect(build({ query: 'senatas' }).source.activeFilterCount.value).toBe(0);
  });

  it('removes one value of a multi-value chip by toggling it, numeric for a year', () => {
    const { source, fake } = build({ query: '', year: [2025, 2026] });

    source.clearChip('year:2026');
    expect(fake.toggleFilter).toHaveBeenCalledWith('year', 2026);
  });

  it('removes a collapsed chip by clearing the whole field', () => {
    const { source, fake } = build({ query: '', institution_ids: ['i1', 'i2'] });

    source.clearChip('institution_ids');
    expect(fake.setFilter).toHaveBeenCalledWith('institution_ids', undefined);
  });

  it('hands facets over with readable labels and drops kinds the page cannot draw', () => {
    const { source } = build();

    expect(source.facets.value).toEqual([
      { field: 'completion_status', label: 'Būsena', type: 'checkbox', values: [{ value: 'incomplete', label: 'Neužpildyta', count: 3, isSelected: true }] },
    ]);
  });

  it('lets a page name a value the way its status badges do, in facets and in chips alike (U10)', () => {
    const fake = makeController({ query: '', completion_status: ['incomplete'] });
    controller.current = fake;
    const source = useTypesenseCollectionSource({
      collection: 'meetings',
      valueLabel: (field, value) => (field === 'completion_status' && value === 'incomplete' ? 'Laukia užpildymo' : undefined),
    });

    expect(source.chips.value[0].label).toBe('Būsena: Laukia užpildymo');
    expect(source.facets.value[0].values[0].label).toBe('Laukia užpildymo');
  });

  it('exposes the institutions the person has duties in, for the "Mano institucijos" quick filter', () => {
    expect(build().source.directInstitutionIds.value).toEqual(['i1', 'i2']);
  });

  it('is not "searched" until the first search comes back, and stays searched after a failure', async () => {
    const { source, fake } = build();
    expect(source.hasSearched.value).toBe(false);

    fake.status.value = 'searching';
    await Promise.resolve();
    fake.status.value = 'idle';
    await Promise.resolve();
    expect(source.hasSearched.value).toBe(true);

    const failed = build();
    failed.fake.error.value = { userMessage: 'Paieška nepavyko.' };
    await Promise.resolve();
    expect(failed.source.hasSearched.value).toBe(true);
    expect(failed.source.error.value).toBe('Paieška nepavyko.');
  });

  it('toggles a year filter with a number and other filters as they are', () => {
    const { source, fake } = build();

    source.toggleFilter('year', '2026');
    source.toggleFilter('completion_status', 'incomplete');

    expect(fake.toggleFilter).toHaveBeenNthCalledWith(1, 'year', 2026);
    expect(fake.toggleFilter).toHaveBeenNthCalledWith(2, 'completion_status', 'incomplete');
  });

  it('overlays an optimistic patch on the loaded rows until undone', () => {
    const { fake, source } = build();
    fake.results.value = [{ id: 'a', is_active: true }, { id: 'b', is_active: true }] as never;

    const undo = source.patchItems(['a'], { is_active: false } as never);
    expect(source.items.value).toEqual([{ id: 'a', is_active: false }, { id: 'b', is_active: true }]);

    // A lagging index returning the old value must not win over the change the user made.
    fake.results.value = [{ id: 'a', is_active: true }, { id: 'b', is_active: true }] as never;
    expect(source.items.value[0]).toEqual({ id: 'a', is_active: false });

    undo();
    expect(source.items.value[0]).toEqual({ id: 'a', is_active: true });
  });

  it('leaves hidden rows out of the list until undone', () => {
    const { fake, source } = build();
    fake.results.value = [{ id: 'a' }, { id: 'b' }] as never;

    const undo = source.hideItems(['a']);
    expect(source.items.value).toEqual([{ id: 'b' }]);

    undo();
    expect(source.items.value).toEqual([{ id: 'a' }, { id: 'b' }]);
  });
});

