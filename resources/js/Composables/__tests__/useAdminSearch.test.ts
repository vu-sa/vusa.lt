import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, reactive, type EffectScope } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import { useAdminSearch } from '../useAdminSearch';

const config = {
  nodes: [{ protocol: 'http', host: 'typesense', port: 8108 }],
  collections: { institutions: { name: 'institutions', key: 'scoped-key', hasAccess: true } },
  headerKey: 'header-key',
  expiresAt: Math.floor(Date.now() / 1000) + 3600,
  isSuperAdmin: false,
};

const json = (body: unknown) => ({ ok: true, status: 200, headers: new Headers(), json: async () => body, text: async () => JSON.stringify(body) });

let nextUserId = 100;
let page: ReturnType<typeof createMockPage>;
const scopes: EffectScope[] = [];
const createSearch = () => {
  const scope = effectScope();
  scopes.push(scope);
  return scope.run(() => useAdminSearch())!;
};

beforeEach(() => {
  page = reactive(createMockPage({ auth: { user: { id: nextUserId++ } }, csrf_token: 'csrf-token' }));
  vi.mocked(usePage).mockReturnValue(page);
  vi.stubGlobal('route', vi.fn((name: string) => `/${name}`));
});

afterEach(() => {
  scopes.splice(0).forEach(scope => scope.stop());
  vi.useRealTimers();
  vi.unstubAllGlobals();
});

describe('useAdminSearch collection searches', () => {
  it('refreshes fifteen minute keys before the next search reaches their expiry', async () => {
    vi.useFakeTimers();
    const initial = { ...config, expiresAt: Math.floor(Date.now() / 1000) + 900 };
    const refreshed = { ...initial, headerKey: 'new-header', expiresAt: initial.expiresAt + 841,
      collections: { institutions: { ...initial.collections.institutions, key: 'new-key' } } };
    const fetchMock = vi.fn().mockResolvedValueOnce(json(initial))
      .mockResolvedValueOnce(json({ success: true, config: refreshed }))
      .mockResolvedValueOnce(json({ results: [{ hits: [{ document: { id: 'i1' } }], found: 1 }] }));
    vi.stubGlobal('fetch', fetchMock);
    const search = createSearch();
    await search.initialize();

    vi.advanceTimersByTime(841_000);
    const result = await search.searchWithFacets('institutions', '*', { facetBy: 'tenant_shortname' });

    expect(fetchMock.mock.calls[1][0]).toBe('/api.v1.admin.search.refreshKey');
    expect(fetchMock.mock.calls[1][1]).toMatchObject({ method: 'POST', headers: { 'X-CSRF-TOKEN': 'csrf-token' } });
    expect(fetchMock.mock.calls[2][1].headers['X-TYPESENSE-API-KEY']).toBe('new-header');
    expect(JSON.parse(fetchMock.mock.calls[2][1].body).searches[0]['x-typesense-api-key']).toBe('new-key');
    expect(result.hits).toEqual([{ id: 'i1' }]);
  });

  it('shares a single config request between callers', async () => {
    const fetchMock = vi.fn().mockResolvedValue(json(config));
    vi.stubGlobal('fetch', fetchMock);
    const first = createSearch();
    const second = createSearch();

    await Promise.all([first.initialize(), second.initialize()]);

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(route).toHaveBeenCalledWith('api.v1.admin.search.config');
    expect(first.config.value).toEqual(second.config.value);
  });

  it('drops the old keys immediately and refetches after a user change', async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(json(config))
      .mockResolvedValueOnce(json({ ...config, headerKey: 'other-user-header' }));
    vi.stubGlobal('fetch', fetchMock);
    const first = createSearch();
    const second = createSearch();
    await first.initialize();

    page.props.auth.user.id = nextUserId++;
    expect(first.config.value).toBeNull();
    expect(second.config.value).toBeNull();
    await Promise.all([first.initialize(), second.initialize()]);

    expect(fetchMock).toHaveBeenCalledTimes(2);
    expect(first.config.value?.headerKey).toBe('other-user-header');
  });

  it('does not restore an old user config when its request finishes late', async () => {
    let resolveOld!: (response: ReturnType<typeof json>) => void;
    const fetchMock = vi.fn().mockImplementationOnce(() => new Promise((resolve) => { resolveOld = resolve; }))
      .mockResolvedValueOnce(json({ ...config, headerKey: 'new-user-header' }));
    vi.stubGlobal('fetch', fetchMock);
    const search = createSearch();
    const oldRequest = search.initialize();
    const rejected = expect(oldRequest).rejects.toMatchObject({ name: 'AbortError' });

    page.props.auth.user.id = nextUserId++;
    await search.initialize();
    resolveOld(json(config));
    await rejected;

    expect(search.config.value?.headerKey).toBe('new-user-header');
  });

  it('sends CSRF before refreshing keys and retrying once', async () => {
    const refreshed = { ...config, headerKey: 'refreshed-header', collections: { institutions: { ...config.collections.institutions, key: 'refreshed-key' } } };
    const fetchMock = vi.fn().mockResolvedValueOnce(json(config))
      .mockResolvedValueOnce({ ok: false, status: 401, headers: new Headers(), text: async () => '' })
      .mockResolvedValueOnce(json({ success: true, config: refreshed }))
      .mockResolvedValueOnce(json({ results: [{ hits: [], found: 0 }] }));
    vi.stubGlobal('fetch', fetchMock);
    const search = createSearch();

    await search.searchWithFacets('institutions', '*', { facetBy: 'tenant_shortname' });

    expect(fetchMock).toHaveBeenCalledTimes(4);
    expect(route).toHaveBeenCalledWith('api.v1.admin.search.refreshKey');
    expect(fetchMock.mock.calls[2][1].headers['X-CSRF-TOKEN']).toBe('csrf-token');
    const retry = JSON.parse(fetchMock.mock.calls[3][1].body);
    expect(retry.searches[0]['x-typesense-api-key']).toBe('refreshed-key');
    expect(fetchMock.mock.calls[3][1].headers['X-TYPESENSE-API-KEY']).toBe('refreshed-header');
  });

  it('deduplicates refresh requests across callers', async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(json(config))
      .mockResolvedValueOnce(json({ success: true, config }));
    vi.stubGlobal('fetch', fetchMock);
    const first = createSearch();
    const second = createSearch();
    await first.initialize();

    await Promise.all([first.refreshConfig(), second.refreshConfig()]);

    expect(fetchMock).toHaveBeenCalledTimes(2);
    expect(first.config.value).toEqual(second.config.value);
  });

  it('stops after one authentication retry', async () => {
    const unauthorized = { ok: false, status: 401, headers: new Headers(), text: async () => '' };
    const fetchMock = vi.fn().mockResolvedValueOnce(json(config))
      .mockResolvedValueOnce(unauthorized)
      .mockResolvedValueOnce(json({ success: true, config }))
      .mockResolvedValueOnce(unauthorized);
    vi.stubGlobal('fetch', fetchMock);

    await expect(createSearch().searchWithFacets('institutions', '*', { facetBy: 'tenant_shortname' })).rejects.toThrow();

    expect(fetchMock).toHaveBeenCalledTimes(4);
  });

  it('keeps collection keys separate and omits inaccessible collections in multi-search', async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(json({ ...config, collections: {
      ...config.collections,
      meetings: { name: 'admin_meetings', key: 'meeting-key', hasAccess: true },
      users: { name: 'admin_users', key: 'forbidden-key', hasAccess: false },
    } })).mockResolvedValueOnce(json({ results: [{ hits: [], found: 0 }, { hits: [], found: 0 }] }));
    vi.stubGlobal('fetch', fetchMock);

    await createSearch().multiSearch('test');

    const { searches } = JSON.parse(fetchMock.mock.calls[1][1].body);
    expect(searches).toHaveLength(2);
    expect(searches).toEqual(expect.arrayContaining([
      expect.objectContaining({ 'collection': 'admin_meetings', 'x-typesense-api-key': 'meeting-key' }),
      expect.objectContaining({ 'collection': 'institutions', 'x-typesense-api-key': 'scoped-key' }),
    ]));
  });

  it('uses server query fields and default sorting instead of client fallbacks', async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(json({ ...config, searchProfiles: { institutions: {
      version: 2,
      parameters: { query_by: 'search_text_en,name_en', query_by_weights: '100,50', infix: 'off,fallback', num_typos: '2,2' },
      defaultSort: 'updated_at:desc',
      facetFields: [],
      sortFields: ['updated_at'],
    } }, searchLocale: 'en' })).mockResolvedValueOnce(json({ results: [{ hits: [], found: 0 }] }));
    vi.stubGlobal('fetch', fetchMock);

    await createSearch().multiSearch('council');

    const { searches } = JSON.parse(fetchMock.mock.calls[1][1].body);
    expect(searches[0]).toMatchObject({
      query_by: 'search_text_en,name_en', query_by_weights: '100,50',
      infix: 'off,fallback', sort_by: '_text_match:desc,updated_at:desc',
    });
  });

  it('rechecks key expiry on access and deduplicates the expired config reload', async () => {
    vi.useFakeTimers();
    const expiring = { ...config, expiresAt: Date.now() / 1000 + 61 };
    const fetchMock = vi.fn().mockResolvedValueOnce(json(expiring)).mockResolvedValueOnce(json(config));
    vi.stubGlobal('fetch', fetchMock);
    const first = createSearch();
    const second = createSearch();
    await first.initialize();
    expect(first.hasExpired.value).toBe(false);

    vi.advanceTimersByTime(2000);
    expect(first.hasExpired.value).toBe(true);
    await Promise.all([first.initialize(), second.initialize()]);

    expect(fetchMock).toHaveBeenCalledTimes(2);
    expect(first.hasExpired.value).toBe(false);
  });

  it('posts to multi_search, so a long filter is not cut off by the 4000-character query-string limit', async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce(json(config))
      .mockResolvedValueOnce(json({ results: [{ found: 196, page: 1, hits: [{ document: { id: 'i1' } }], facet_counts: [] }] }));
    vi.stubGlobal('fetch', fetchMock);

    const filterBy = `id:=[${Array.from({ length: 196 }, (_, index) => `01k${String(index).padStart(23, '0')}`).join(',')}]`;
    const result = await createSearch().searchWithFacets('institutions', '*', { filterBy, facetBy: 'tenant_shortname' });

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

    await expect(createSearch().searchWithFacets('institutions', '*', { facetBy: 'tenant_shortname' })).rejects.toThrow();
  });

  it('lets searches through again once the Retry-After window has passed', async () => {
    vi.useFakeTimers();
    const fetchMock = vi.fn()
      .mockResolvedValueOnce(json(config))
      .mockResolvedValueOnce({ ok: false, status: 429, headers: new Headers({ 'retry-after': '2' }), json: async () => ({}), text: async () => '' });
    vi.stubGlobal('fetch', fetchMock);

    const search = createSearch();
    await expect(search.searchWithFacets('institutions', '*', { facetBy: 'tenant_shortname' })).rejects.toThrow();
    expect(search.isRateLimited.value).toBe(true);
    expect(createSearch().isRateLimited.value).toBe(true);

    vi.advanceTimersByTime(2001);

    expect(search.isRateLimited.value).toBe(false);
    vi.useRealTimers();
  });
});
