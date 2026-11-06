import { afterEach, describe, expect, it, vi } from 'vitest';

import { useAdminSearch } from '../useAdminSearch';

const config = {
  nodes: [{ protocol: 'http', host: 'typesense', port: 8108 }],
  collections: { institutions: { name: 'institutions', key: 'scoped-key', hasAccess: true } },
  headerKey: 'header-key',
  expiresAt: Math.floor(Date.now() / 1000) + 3600,
  isSuperAdmin: false,
};

const json = (body: unknown) => ({ ok: true, status: 200, headers: new Headers(), json: async () => body, text: async () => JSON.stringify(body) });

describe('useAdminSearch collection searches', () => {
  afterEach(() => vi.unstubAllGlobals());

  it('posts to multi_search, so a long filter is not cut off by the 4000-character query-string limit', async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce(json(config))
      .mockResolvedValueOnce(json({ results: [{ found: 196, page: 1, hits: [{ document: { id: 'i1' } }], facet_counts: [] }] }));
    vi.stubGlobal('fetch', fetchMock);

    const filterBy = `id:=[${Array.from({ length: 196 }, (_, index) => `01k${String(index).padStart(23, '0')}`).join(',')}]`;
    const result = await useAdminSearch().searchWithFacets('institutions', '*', { filterBy, facetBy: 'tenant_shortname' });

    const [url, init] = fetchMock.mock.calls[1];
    const search = JSON.parse(init.body).searches[0];
    expect(url).toBe('http://typesense:8108/multi_search');
    expect(init.method).toBe('POST');
    expect(init.headers['X-TYPESENSE-API-KEY']).toBe('header-key');
    expect(search['x-typesense-api-key']).toBe('scoped-key');
    expect(search.filter_by).toBe(filterBy);
    expect(result.totalHits).toBe(196);
  });

  it('reports a failure multi_search returns inside a 200', async () => {
    vi.stubGlobal('fetch', vi.fn()
      .mockResolvedValueOnce(json(config))
      .mockResolvedValueOnce(json({ results: [{ code: 400, error: 'Could not parse the filter query.' }] })));

    await expect(useAdminSearch().searchWithFacets('institutions', '*', { facetBy: 'tenant_shortname' })).rejects.toThrow();
  });

  it('lets searches through again once the Retry-After window has passed', async () => {
    vi.useFakeTimers();
    const fetchMock = vi.fn()
      .mockResolvedValueOnce(json(config))
      .mockResolvedValueOnce({ ok: false, status: 429, headers: new Headers({ 'retry-after': '2' }), json: async () => ({}), text: async () => '' });
    vi.stubGlobal('fetch', fetchMock);

    const search = useAdminSearch();
    await expect(search.searchWithFacets('institutions', '*', { facetBy: 'tenant_shortname' })).rejects.toThrow();
    expect(search.isRateLimited.value).toBe(true);

    vi.advanceTimersByTime(2001);

    expect(search.isRateLimited.value).toBe(false);
    vi.useRealTimers();
  });
});
