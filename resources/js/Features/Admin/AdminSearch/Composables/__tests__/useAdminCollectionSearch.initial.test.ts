import { flushPromises, mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { useAdminCollectionSearch } from '../useAdminCollectionSearch';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const config = {
  nodes: [{ protocol: 'http', host: 'typesense', port: 8108 }],
  collections: { meetings: { name: 'admin_meetings', key: 'meeting-key', hasAccess: true } },
  headerKey: 'header-key',
  expiresAt: Math.floor(Date.now() / 1000) + 3600,
};
const json = (body: unknown) => ({ ok: true, status: 200, headers: new Headers(), json: async () => body });
const yearFacet = (counts: Array<{ value: string; count: number }>) => ({ field_name: 'year', counts });
let userId = 1000;
let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { user: { id: userId++ } } }));
  vi.stubGlobal('route', vi.fn((name: string) => `/${name}`));
  localStorage.clear();
  window.history.replaceState({}, '', '/mano/meetings');
});

afterEach(() => {
  wrapper?.unmount();
  vi.unstubAllGlobals();
});

async function mountSearch(baseFilterBy?: string, collection: 'meetings' | 'institutions' = 'meetings') {
  let search!: ReturnType<typeof useAdminCollectionSearch>;
  wrapper = mount(defineComponent({
    setup() {
      search = useAdminCollectionSearch({ collection, baseFilterBy });
      return () => null;
    },
  }));
  await flushPromises();
  return search;
}

describe('collection first request', () => {
  it.each([true, false])('respects activity field visibility when excluded is %s', async (excluded) => {
    window.history.replaceState({}, '', '/mano/institutions?activity_status=overdue');
    const fetchMock = vi.fn().mockResolvedValueOnce(json({ ...config, collections: {
      institutions: { name: 'institutions', key: 'institution-key', hasAccess: true, excludedFields: excluded ? ['activity_status'] : [] },
    } })).mockResolvedValueOnce(json({ results: [{
      hits: [{ document: { id: 'i1', name_lt: 'Taryba' } }], found: 1,
      facet_counts: excluded ? [] : [{ field_name: 'activity_status', counts: [{ value: 'overdue', count: 1 }] }],
    }] }));
    vi.stubGlobal('fetch', fetchMock);

    const search = await mountSearch(undefined, 'institutions');

    const { searches } = JSON.parse(fetchMock.mock.calls[1][1].body);
    expect(searches[0].facet_by.includes('activity_status')).toBe(!excluded);
    expect((searches[0].filter_by ?? '').includes('activity_status')).toBe(!excluded);
    expect(search.facets.value.some(facet => facet.field === 'activity_status')).toBe(!excluded);
    expect(search.results.value).toEqual([{ id: 'i1', name_lt: 'Taryba' }]);
    expect(search.error.value).toBeNull();
  });

  it('loads rows and initial facets in one search on an unfiltered visit', async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(json(config)).mockResolvedValueOnce(json({
      results: [{ hits: [{ document: { id: 'm1' } }], found: 1, facet_counts: [yearFacet([{ value: '2025', count: 1 }])] }],
    }));
    vi.stubGlobal('fetch', fetchMock);

    const search = await mountSearch();

    expect(fetchMock).toHaveBeenCalledTimes(2);
    expect(fetchMock.mock.calls[1][0]).toBe('http://typesense:8108/multi_search');
    const { searches } = JSON.parse(fetchMock.mock.calls[1][1].body);
    expect(searches).toHaveLength(1);
    expect(Number(searches[0].per_page)).toBe(24);
    expect(search.initialFacetsLoaded.value).toBe(true);
    expect(search.results.value).toEqual([{ id: 'm1' }]);
  });

  it('awaits the first search when facets are requested while it is in flight', async () => {
    let resolveSearch!: (response: ReturnType<typeof json>) => void;
    const fetchMock = vi.fn().mockResolvedValueOnce(json(config))
      .mockImplementationOnce(() => new Promise(resolve => { resolveSearch = resolve; }));
    vi.stubGlobal('fetch', fetchMock);
    const search = await mountSearch();
    let finished = false;

    const facets = search.loadInitialFacets().then(() => { finished = true; });
    await flushPromises();
    expect(finished).toBe(false);
    expect(fetchMock).toHaveBeenCalledTimes(2);

    resolveSearch(json({ results: [{ hits: [], found: 0, facet_counts: [yearFacet([{ value: '2025', count: 2 }])] }] }));
    await facets;

    expect(search.initialFacetsLoaded.value).toBe(true);
    expect(fetchMock).toHaveBeenCalledTimes(2);
  });

  it('applies URL state before the only search POST and merges disjunctive counts', async () => {
    window.history.replaceState({}, '', '/mano/meetings?q=senatas&year=2025&completion_status=complete');
    const fetchMock = vi.fn().mockResolvedValueOnce(json(config)).mockResolvedValueOnce(json({
      results: [
        { hits: [], found: 0, facet_counts: [yearFacet([{ value: '2025', count: 0 }])] },
        { facet_counts: [yearFacet([{ value: '2025', count: 2 }, { value: '2024', count: 3 }])] },
        { facet_counts: [{ field_name: 'completion_status', counts: [{ value: 'complete', count: 2 }, { value: 'incomplete', count: 4 }] }] },
        { facet_counts: [yearFacet([{ value: '2025', count: 5 }, { value: '2024', count: 6 }]), { field_name: 'completion_status', counts: [{ value: 'complete', count: 5 }, { value: 'incomplete', count: 6 }] }] },
      ],
    }));
    vi.stubGlobal('fetch', fetchMock);

    const search = await mountSearch('tenant_id:=[7]');

    expect(fetchMock).toHaveBeenCalledTimes(2);
    const { searches } = JSON.parse(fetchMock.mock.calls[1][1].body);
    expect(searches).toHaveLength(4);
    expect(searches[0].q).toBe('senatas');
    expect(searches[0].filter_by).toContain('year:[2025]');
    expect(searches[0].filter_by).toContain('completion_status:=');
    expect(searches[0].sort_by).toBe('_text_match:desc,start_time:desc');
    expect(searches[1].facet_by).toBe('year');
    expect(searches[1].filter_by).not.toContain('year:');
    expect(searches[1].filter_by).toContain('completion_status:=');
    expect(searches[2].filter_by).toContain('year:[2025]');
    expect(searches[2].filter_by).not.toContain('completion_status:=');
    expect(searches[3]).toMatchObject({ q: '*', filter_by: 'tenant_id:=[7]', per_page: 0 });
    for (const request of searches) {
      expect(request.collection).toBe('admin_meetings');
      expect(request['x-typesense-api-key']).toBe('meeting-key');
      expect(request.filter_by).toContain('tenant_id:=[7]');
    }
    expect(search.facets.value.find(facet => facet.field === 'year')?.values).toEqual(expect.arrayContaining([
      expect.objectContaining({ value: '2024', count: 3 }),
      expect.objectContaining({ value: '2025', count: 2, isSelected: true }),
    ]));
  });
});
