import { flushPromises, mount } from '@vue/test-utils';
import { defineComponent, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useAdminCollectionSearch } from '../useAdminCollectionSearch';

const search = vi.hoisted(() => ({ searchWithFacets: vi.fn(), totalPages: 20 }));

vi.mock('@/Composables/useAdminSearch', () => ({
  useAdminSearch: () => ({
    config: ref(null),
    isReady: ref(true),
    initialize: vi.fn().mockResolvedValue(undefined),
    hasCollectionAccess: () => true,
    getCollectionApiKey: () => 'key',
    loadInitialFacets: vi.fn().mockResolvedValue([]),
    searchWithFacets: search.searchWithFacets,
  }),
}));

async function mountController(options: Partial<Parameters<typeof useAdminCollectionSearch>[0]> = {}) {
  let controller!: ReturnType<typeof useAdminCollectionSearch>;

  mount(defineComponent({
    setup() {
      controller = useAdminCollectionSearch({ collection: 'meetings', syncToUrl: true, perPage: 2, ...options });

      return () => null;
    },
  }));
  await flushPromises();

  return controller;
}

const pagesCalled = () => search.searchWithFacets.mock.calls.map(call => call[2].page);

beforeEach(() => {
  localStorage.clear();
  search.totalPages = 20;
  search.searchWithFacets.mockReset();
  search.searchWithFacets.mockImplementation(async (_collection: string, _query: string, options: { page: number }) => ({
    hits: [{ id: `a${options.page}` }, { id: `b${options.page}` }],
    totalHits: search.totalPages * 2,
    totalPages: search.totalPages,
    facets: [],
  }));
  window.history.replaceState({}, '', '/mano/meetings');
});

describe('useAdminCollectionSearch — restoring loaded pages', () => {
  it('loads one page when the URL says nothing', async () => {
    const controller = await mountController();

    expect(pagesCalled()).toEqual([1]);
    expect(controller.results.value).toHaveLength(2);
  });

  it('replays the pages that were loaded before leaving, so back lands on the same rows', async () => {
    window.history.replaceState({}, '', '/mano/meetings?pages=3');
    const controller = await mountController();

    expect(pagesCalled()).toEqual([1, 2, 3]);
    expect((controller.results.value as { id: string }[]).map(hit => hit.id)).toEqual(['a1', 'b1', 'a2', 'b2', 'a3', 'b3']);
    expect(new URLSearchParams(window.location.search).get('pages')).toBe('3');
  });

  it('records the pages as the person loads more', async () => {
    const controller = await mountController();
    expect(new URLSearchParams(window.location.search).has('pages')).toBe(false);

    await controller.loadMore();
    await controller.loadMore();

    expect(new URLSearchParams(window.location.search).get('pages')).toBe('3');
  });

  it('never replays more pages than exist', async () => {
    search.totalPages = 2;
    window.history.replaceState({}, '', '/mano/meetings?pages=5');
    await mountController();

    expect(pagesCalled()).toEqual([1, 2]);
  });

  it('caps the replay, so a hand-edited URL cannot fan out into hundreds of requests', async () => {
    window.history.replaceState({}, '', '/mano/meetings?pages=500');
    await mountController();

    expect(search.searchWithFacets).toHaveBeenCalledTimes(10);
  });

  it('ignores a pages value that is not a sensible number', async () => {
    window.history.replaceState({}, '', '/mano/meetings?pages=abc');
    await mountController();

    expect(pagesCalled()).toEqual([1]);
  });

  it('forgets the loaded pages once a new search starts', async () => {
    window.history.replaceState({}, '', '/mano/meetings?pages=3');
    const controller = await mountController();

    controller.search('senatas', true);
    await flushPromises();

    expect(new URLSearchParams(window.location.search).has('pages')).toBe(false);
    expect(new URLSearchParams(window.location.search).get('q')).toBe('senatas');
  });

  it('keeps the query-string keys the page owns when it syncs state to the URL', async () => {
    window.history.replaceState({}, '', '/mano/meetings?view=table&item=abc');
    const controller = await mountController({ preserveUrlKeys: ['view', 'item'] });
    controller.search('x', true);
    await flushPromises();

    const params = new URLSearchParams(window.location.search);
    expect(params.get('view')).toBe('table');
    expect(params.get('item')).toBe('abc');
  });
});

describe('useAdminCollectionSearch — reactive base filter', () => {
  it('searches again, with the new clause, when the base filter changes', async () => {
    const baseFilterBy = ref<string | undefined>(undefined);
    await mountController({ baseFilterBy });

    baseFilterBy.value = 'id:=[a,b]';
    await flushPromises();

    const filters = search.searchWithFacets.mock.calls.map(call => call[2].filterBy);
    expect(filters).toEqual([undefined, 'id:=[a,b]']);
  });
});

describe('useAdminCollectionSearch — default filters', () => {
  it('starts a visit without URL filters on the defaults', async () => {
    const controller = await mountController({ defaultFilters: { tenant_shortnames: ['VU SA MIF'] } });

    expect(controller.filters.value.tenant_shortnames).toEqual(['VU SA MIF']);
  });

  it('lets the URL win over the defaults', async () => {
    window.history.replaceState({}, '', '/mano/meetings?year=2025');
    const controller = await mountController({ defaultFilters: { tenant_shortnames: ['VU SA MIF'] } });

    expect(controller.filters.value.tenant_shortnames).toBeUndefined();
    expect(controller.filters.value.year).toEqual([2025]);
  });

  it('skips an empty default, e.g. a user with no current duties', async () => {
    const controller = await mountController({ defaultFilters: { tenant_shortnames: [] } });

    expect(controller.filters.value.tenant_shortnames).toBeUndefined();
  });
});

describe('useAdminCollectionSearch — remembered filters', () => {
  it('comes back to the filters the list was left with, over the defaults', async () => {
    const first = await mountController({ defaultFilters: { tenant_shortnames: ['VU SA MIF'] } });
    first.setFilter('year', [2024]);
    // Skip the debounce: an immediate search syncs (and remembers) the same state.
    first.search('', true);
    await flushPromises();

    window.history.replaceState({}, '', '/mano/meetings');
    const second = await mountController({ defaultFilters: { tenant_shortnames: ['VU SA MIF'] } });

    expect(second.filters.value.year).toEqual([2024]);
  });

  it('keeps a list the user cleared cleared, instead of reapplying the defaults', async () => {
    const first = await mountController({ defaultFilters: { tenant_shortnames: ['VU SA MIF'] } });
    first.clearFilters();
    first.search('', true);
    await flushPromises();

    window.history.replaceState({}, '', '/mano/meetings');
    const second = await mountController({ defaultFilters: { tenant_shortnames: ['VU SA MIF'] } });

    expect(second.filters.value.tenant_shortnames).toBeUndefined();
  });
});
