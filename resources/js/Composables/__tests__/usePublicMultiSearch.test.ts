/**
 * Tests for usePublicMultiSearch — the unified public search composable.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { flushPromises } from '@vue/test-utils';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { trackEvent } from '@/Plugins/umami';

vi.mock('@/Plugins/umami', () => ({ trackEvent: vi.fn() }));

// Array-friendly localStorage mock (the default helper spreads objects, which breaks arrays).
// Backed by a real `ref` so computeds that depend on it (e.g. `totalResultCount`) stay reactive.
vi.mock('@vueuse/core', () => ({
  useLocalStorage: vi.fn((_key: string, defaultValue: any) => ref(Array.isArray(defaultValue) ? [...defaultValue] : defaultValue)),
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

  it('falls back to browsing everything for queries shorter than the minimum length', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search('a', true);
    await flushPromises();

    expect(mockFetch).toHaveBeenCalledTimes(1);
    const body = JSON.parse(mockFetch.mock.calls[0][1].body);
    expect(body.searches.every((s: { q: string }) => s.q === '*')).toBe(true);
    expect(controller.hasAnyResults.value).toBe(true);
  });

  it('runs a wildcard browse search on demand, populating results and counts', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search(controller.browseQuery, true);
    await flushPromises();

    expect(mockFetch).toHaveBeenCalledTimes(1);
    const body = JSON.parse(mockFetch.mock.calls[0][1].body);
    expect(body.searches).toHaveLength(6);
    expect(body.searches.every((s: { q: string }) => s.q === '*')).toBe(true);

    expect(controller.hasSearched.value).toBe(true);
    expect(controller.sections.documents.totalHits).toBe(5);
    expect(controller.totalResultCount.value).toBeGreaterThan(0);
  });

  it('returns to browsing everything when the search box is cleared', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search('studentai', true);
    await flushPromises();
    expect(controller.query.value).toBe('studentai');

    controller.clearFilters();
    await flushPromises();

    expect(controller.query.value).toBe('*');
    expect(controller.displayQuery.value).toBe('');
    expect(controller.hasAnyResults.value).toBe(true);
  });

  it('shows every collection when none are selected (no filter)', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    expect(controller.enabledCollections.value).toEqual([]);

    controller.search('studentai', true);
    await flushPromises();

    // All six are still queried, and — with no filter selected — all are displayed.
    expect(controller.totalResultCount.value).toBe(3 + 2 + 5 + 1 + 0 + 4);
    expect(controller.orderedSections.value).toContain('news');
  });

  it('narrows results to the selected collections once at least one is checked', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search('studentai', true);
    await flushPromises();

    controller.toggleCollection('news');
    await flushPromises();

    const body = JSON.parse(mockFetch.mock.calls.at(-1)![1].body);
    const collections = body.searches.map((s: { collection: string }) => s.collection);
    // Every collection is still queried, so counts stay accurate for the unchecked ones too.
    expect(collections).toEqual([
      'public_institutions',
      'public_meetings',
      'documents',
      'news',
      'pages',
      'calendar',
    ]);

    // ...but only the checked collection is displayed.
    expect(controller.sections.documents.totalHits).toBe(5);
    expect(controller.orderedSections.value).toEqual(['news']);
    expect(controller.totalResultCount.value).toBe(1);
  });

  it('fetches a larger page for visible collections once filtered, and a minimal one for hidden collections', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search('studentai', true);
    await flushPromises();
    // No filter yet: every collection uses the browse/preview page size.
    let body = JSON.parse(mockFetch.mock.calls[0][1].body);
    expect(body.searches.find((s: { collection: string }) => s.collection === 'documents').per_page).toBe(6);

    controller.toggleCollection('documents');
    await flushPromises();

    body = JSON.parse(mockFetch.mock.calls.at(-1)![1].body);
    const perPageFor = (collection: string) =>
      body.searches.find((s: { collection: string }) => s.collection === collection).per_page;
    expect(perPageFor('documents')).toBe(12); // visible — real page size
    expect(perPageFor('news')).toBe(1); // hidden — just enough for the count
  });

  it('resetCollections clears the filter back to "show everything" and refetches', async () => {
    const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
    const controller = usePublicMultiSearch();

    controller.search('studentai', true);
    await flushPromises();

    controller.toggleCollection('news');
    await flushPromises();
    expect(controller.totalResultCount.value).toBe(1);

    controller.resetCollections();
    await flushPromises();

    expect(controller.enabledCollections.value).toEqual([]);
    expect(controller.totalResultCount.value).toBe(3 + 2 + 5 + 1 + 0 + 4);
  });

  describe('search term analytics', () => {
    // The fake response totals 3 + 2 + 5 + 1 + 0 + 4 across the six collections.
    const TOTAL_HITS = 15;

    it('reports a submitted search with the term and total result count', async () => {
      const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
      const controller = usePublicMultiSearch();

      controller.search('studentai', true);
      await flushPromises();

      expect(trackEvent).toHaveBeenCalledTimes(1);
      expect(trackEvent).toHaveBeenCalledWith('search_submitted', {
        term: 'studentai',
        results: TOTAL_HITS,
      });
    });

    it('lowercases and truncates the reported term', async () => {
      const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
      const controller = usePublicMultiSearch();

      controller.search(`  STUDENTŲ ${'a'.repeat(80)}  `, true);
      await flushPromises();

      const [, payload] = vi.mocked(trackEvent).mock.calls[0];
      expect(payload!.term).toHaveLength(60);
      expect(payload!.term).toBe(`studentų ${'a'.repeat(80)}`.slice(0, 60));
    });

    it('reports only the settled term, not each keystroke', async () => {
      vi.useFakeTimers();

      const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
      const controller = usePublicMultiSearch();

      // Type progressively; each keystroke goes through the as-you-type debounce.
      controller.search('vu');
      await vi.advanceTimersByTimeAsync(400);
      controller.search('vusa');
      await vi.advanceTimersByTimeAsync(400);

      // Results have been fetched, but nothing is reported until typing settles.
      expect(mockFetch).toHaveBeenCalledTimes(2);
      expect(trackEvent).not.toHaveBeenCalled();

      await vi.advanceTimersByTimeAsync(2000);

      expect(trackEvent).toHaveBeenCalledTimes(1);
      expect(trackEvent).toHaveBeenCalledWith('search_submitted', {
        term: 'vusa',
        results: TOTAL_HITS,
      });

      vi.useRealTimers();
    });

    it('refetches with new page sizes but does not re-report the same term when a collection is toggled', async () => {
      const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
      const controller = usePublicMultiSearch();

      controller.search('studentai', true);
      await flushPromises();
      expect(trackEvent).toHaveBeenCalledTimes(1);
      expect(mockFetch).toHaveBeenCalledTimes(1);

      // Toggling narrows/widens which collections are visible, which changes their page size,
      // so it must refetch — but it's still the same term, so analytics aren't re-reported.
      controller.toggleCollection('news');
      await flushPromises();

      expect(mockFetch).toHaveBeenCalledTimes(2);
      expect(trackEvent).toHaveBeenCalledTimes(1);
    });

    it('reports searches that found nothing', async () => {
      mockFetch.mockImplementation(async (_url: string, init: { body: string }) => {
        const { searches } = JSON.parse(init.body);
        return {
          ok: true,
          json: async () => ({ results: searches.map(() => ({ found: 0, hits: [] })) }),
        };
      });

      const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
      const controller = usePublicMultiSearch();

      controller.search('nerandama', true);
      await flushPromises();

      expect(trackEvent).toHaveBeenCalledWith('search_submitted', {
        term: 'nerandama',
        results: 0,
      });
    });

    it('does not report terms below the minimum length', async () => {
      const { usePublicMultiSearch } = await import('../usePublicMultiSearch');
      const controller = usePublicMultiSearch();

      controller.search('a', true);
      await flushPromises();

      expect(trackEvent).not.toHaveBeenCalled();
    });
  });
});
