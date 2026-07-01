/**
 * Tests for usePublicMultiSearch — the unified public search composable.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { flushPromises } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';

// Array-friendly localStorage mock (the default helper spreads objects, which breaks arrays).
vi.mock('@vueuse/core', () => ({
  useLocalStorage: vi.fn((_key: string, defaultValue: any) => ({
    value: Array.isArray(defaultValue) ? [...defaultValue] : defaultValue,
  })),
}));

/**
 * Build a Typesense `/multi_search` response with one result per requested search,
 * preserving the order the searches were sent in.
 */
const fakeMultiSearchResponse = (searches: Array<{ collection: string }>) => ({
  results: searches.map((search) => {
    const scores: Record<string, { score: number; found: number }> = {
      public_institutions: { score: 10, found: 3 },
      public_meetings: { score: 50, found: 2 },
      documents: { score: 99, found: 5 },
      news: { score: 5, found: 1 },
      pages: { score: 0, found: 0 },
      calendar: { score: 20, found: 4 },
    };
    const meta = scores[search.collection] ?? { score: 1, found: 1 };
    return {
      found: meta.found,
      hits: meta.found > 0
        ? [{ document: { id: `${search.collection}-1`, title: `${search.collection} hit` }, text_match: meta.score }]
        : [],
    };
  }),
});

const mockFetch = vi.fn();

describe('usePublicMultiSearch', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.stubGlobal('fetch', mockFetch);
    mockFetch.mockImplementation(async (_url: string, init: { body: string }) => {
      const { searches } = JSON.parse(init.body);
      return {
        ok: true,
        json: async () => fakeMultiSearchResponse(searches),
      };
    });

    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        app: { locale: 'lt' },
        typesenseConfig: {
          apiKey: 'search-only-key',
          nodes: [{ protocol: 'http', host: 'localhost', port: 8108 }],
          collections: {
            news: 'news',
            pages: 'pages',
            calendar: 'calendar',
            documents: 'documents',
            public_meetings: 'public_meetings',
            public_institutions: 'public_institutions',
          },
        },
      }) as any,
    );
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('searches all six public collections in a single multi_search request', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search('studentai', true);
    await flushPromises();

    expect(mockFetch).toHaveBeenCalledTimes(1);
    const [url, init] = mockFetch.mock.calls[0];
    expect(url).toBe('http://localhost:8108/multi_search');
    expect(init.headers['X-TYPESENSE-API-KEY']).toBe('search-only-key');

    const body = JSON.parse(init.body);
    const collections = body.searches.map((s: { collection: string }) => s.collection);
    expect(collections).toEqual([
      'public_institutions',
      'public_meetings',
      'documents',
      'news',
      'pages',
      'calendar',
    ]);
    // public_meetings is included (added on top of the legacy dialog set)
    expect(collections).toContain('public_meetings');
  });

  it('distributes results per section and orders sections by relevance', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search('studentai', true);
    await flushPromises();

    expect(controller.sections.documents.totalHits).toBe(5);
    expect(controller.sections.documents.hits).toHaveLength(1);
    expect(controller.sections.documents.hasMore).toBe(true);

    // pages returns 0 hits → excluded from ordered sections
    expect(controller.orderedSections.value).toEqual([
      'documents', // score 99
      'meetings', // 50
      'calendar', // 20
      'institutions', // 10
      'news', // 5
    ]);
    expect(controller.totalResultCount.value).toBe(3 + 2 + 5 + 1 + 0 + 4);
  });

  it('appends the next page when loading more for a single collection', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search('studentai', true);
    await flushPromises();
    expect(controller.sections.documents.hits).toHaveLength(1);

    // Next page returns another document hit.
    mockFetch.mockImplementationOnce(async (_url: string, init: { body: string }) => {
      const { searches } = JSON.parse(init.body);
      expect(searches).toHaveLength(1);
      expect(searches[0].collection).toBe('documents');
      expect(searches[0].page).toBe(2);
      return {
        ok: true,
        json: async () => ({ results: [{ found: 5, hits: [{ document: { id: 'documents-2' }, text_match: 90 }] }] }),
      };
    });

    await controller.loadMore('documents');
    await flushPromises();

    expect(controller.sections.documents.hits).toHaveLength(2);
    expect(controller.sections.documents.page).toBe(2);
  });

  it('ignores queries shorter than the minimum length', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search('a', true);
    await flushPromises();

    expect(mockFetch).not.toHaveBeenCalled();
    expect(controller.hasAnyResults.value).toBe(false);
  });

  it('excludes toggled-off collections from the request', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.toggleCollection('news');
    controller.toggleCollection('pages');
    controller.search('studentai', true);
    await flushPromises();

    const lastCall = mockFetch.mock.calls.at(-1)!;
    const body = JSON.parse(lastCall[1].body);
    const collections = body.searches.map((s: { collection: string }) => s.collection);
    expect(collections).not.toContain('news');
    expect(collections).not.toContain('pages');
    expect(collections).toContain('documents');
  });
});
