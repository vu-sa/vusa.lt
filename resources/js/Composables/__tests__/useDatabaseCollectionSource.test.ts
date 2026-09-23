import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { useDatabaseCollectionSource, type DatabaseFacetDefinition } from '../useCollectionSource';

const facets: DatabaseFacetDefinition[] = [
  { field: 'scope', label: 'Rodyti', single: true, values: [{ value: 'mine', label: 'Mano' }, { value: 'administered', label: 'Administruoju' }] },
  { field: 'state', label: 'Būsena', values: [{ value: 'created', label: 'Sukurta' }, { value: 'lent', label: 'Išduota' }] },
];

const respond = (items: unknown[] = []) => vi.fn().mockResolvedValue({
  ok: true,
  json: async () => ({ success: true, data: { items, total: items.length, per_page: 20, current_page: 1, last_page: 1 } }),
});

function makeSource() {
  return useDatabaseCollectionSource({
    endpoint: '/api/v1/admin/reservations',
    initial: { items: [], total: 0, perPage: 20, currentPage: 1, lastPage: 1 },
    sortOptions: [],
    defaultSort: 'start_time:desc',
    facets,
  });
}

const lastUrl = (fetchMock: ReturnType<typeof vi.fn>) => new URL(String(fetchMock.mock.calls.at(-1)?.[0]));

describe('useDatabaseCollectionSource filters', () => {
  beforeEach(() => window.history.replaceState({}, '', '/mano/reservations'));
  afterEach(() => vi.unstubAllGlobals());

  it('reads its filters from the page URL and ignores unknown values', () => {
    window.history.replaceState({}, '', '/mano/reservations?scope=administered&state=created,nonsense');

    const source = makeSource();

    expect(source.filters.value).toEqual({ scope: 'administered', state: ['created'] });
    expect(source.activeFilterCount.value).toBe(2);
  });

  it('sends a multi-value filter as field[] and a single one as a plain param', async () => {
    const fetchMock = respond();
    vi.stubGlobal('fetch', fetchMock);
    const source = makeSource();

    source.toggleFilter('scope', 'mine');
    source.toggleFilter('state', 'created');
    source.toggleFilter('state', 'lent');
    await vi.waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(3));

    const url = lastUrl(fetchMock);
    expect(url.searchParams.get('scope')).toBe('mine');
    expect(url.searchParams.getAll('state[]')).toEqual(['created', 'lent']);
  });

  it('toggling the same value again clears it', () => {
    vi.stubGlobal('fetch', respond());
    const source = makeSource();

    source.toggleFilter('state', 'lent');
    source.toggleFilter('state', 'lent');

    expect(source.filters.value).toEqual({});
  });

  it('carries the filters in the URL after a fetch', async () => {
    vi.stubGlobal('fetch', respond());
    const source = makeSource();

    source.setFilter('state', ['created', 'lent']);
    await vi.waitFor(() => expect(window.location.search).toContain('state=created%2Clent'));
  });

  it('exposes one removable chip per selected value', () => {
    vi.stubGlobal('fetch', respond());
    const source = makeSource();

    source.setFilter('state', ['created', 'lent']);
    expect(source.chips.value.map(chip => chip.label)).toEqual(['Būsena: Sukurta', 'Būsena: Išduota']);

    source.clearChip('state:created');
    expect(source.filters.value).toEqual({ state: ['lent'] });
  });

  it('lets only the latest request write its rows', async () => {
    const stale = { ok: true, json: async () => ({ success: true, data: { items: ['stale'], total: 1, per_page: 20, current_page: 1, last_page: 1 } }) };
    const fresh = { ok: true, json: async () => ({ success: true, data: { items: ['fresh'], total: 1, per_page: 20, current_page: 1, last_page: 1 } }) };
    let releaseStale: (value: unknown) => void = () => undefined;
    const fetchMock = vi.fn()
      .mockReturnValueOnce(new Promise((resolve) => { releaseStale = resolve; }))
      .mockResolvedValueOnce(fresh);
    vi.stubGlobal('fetch', fetchMock);
    const source = makeSource();

    source.setFilter('scope', 'mine');
    source.setFilter('scope', 'administered');
    await vi.waitFor(() => expect(source.items.value).toEqual(['fresh']));

    releaseStale(stale);
    await new Promise(resolve => setTimeout(resolve, 0));

    expect(source.items.value).toEqual(['fresh']);
  });

  it('clearFilters drops everything', () => {
    vi.stubGlobal('fetch', respond());
    const source = makeSource();

    source.setFilter('scope', 'mine');
    source.clearFilters();

    expect(source.filters.value).toEqual({});
  });
});

describe('useDatabaseCollectionSource without a first page', () => {
  beforeEach(() => window.history.replaceState({}, '', '/mano/pages?showDeleted=true'));
  afterEach(() => vi.unstubAllGlobals());

  it('shows the skeleton and fetches the first page itself', async () => {
    const fetchMock = respond([{ id: 'a' }]);
    vi.stubGlobal('fetch', fetchMock);

    const source = useDatabaseCollectionSource({
      endpoint: '/api/v1/admin/trash/pages',
      initial: { items: [], total: 0, perPage: 50, currentPage: 0, lastPage: 1 },
      sortOptions: [],
      defaultSort: 'deleted_at:desc',
      preserveUrlKeys: ['showDeleted'],
      fetchOnMount: true,
    });

    expect(source.hasSearched.value).toBe(false);
    await vi.waitFor(() => expect(source.hasSearched.value).toBe(true));
    expect(source.items.value).toEqual([{ id: 'a' }]);
    expect(lastUrl(fetchMock).searchParams.get('showDeleted')).toBe('true');
  });
});
