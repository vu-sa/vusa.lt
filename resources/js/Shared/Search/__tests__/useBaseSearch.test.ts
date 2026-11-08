import { flushPromises, mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { describe, expect, it, vi } from 'vitest';

import { useBaseSearch } from '../composables/useBaseSearch';
import type { BaseFacet, BaseSearchFilters, ProcessedSearchResult } from '../types';

const response = (id: string): ProcessedSearchResult<BaseFacet> => ({
  hits: [{ id }], totalHits: 4, totalPages: 2, currentPage: 1, facets: [],
});

function createSearch() {
  const performSearch = vi.fn().mockResolvedValue(response('initial'));
  let search!: ReturnType<typeof useBaseSearch>;
  mount(defineComponent({
    setup() {
      search = useBaseSearch<BaseSearchFilters, BaseFacet>({
        defaultFilters: { query: '*' },
        searchService: { performSearch, loadInitialFacets: async () => [] },
        filterKeyMapper: () => [],
        debounceDelay: 10,
      });
      return () => null;
    },
  }));
  return { search, performSearch };
}

describe('base search request ordering', () => {
  it('runs a query typed during a search and ignores the older response', async () => {
    const { search, performSearch } = createSearch();
    let resolveOld!: (value: ProcessedSearchResult<BaseFacet>) => void;
    performSearch.mockImplementationOnce(() => new Promise(resolve => { resolveOld = resolve; }))
      .mockResolvedValueOnce(response('latest'));
    search.search('older', true);

    vi.useFakeTimers();
    try {
      search.search('latest');
      await vi.advanceTimersByTimeAsync(10);
      expect(performSearch.mock.calls.at(-1)?.[0].query).toBe('latest');
      expect(search.results.value).toEqual([{ id: 'latest' }]);

      resolveOld(response('older'));
      await flushPromises();

      expect(search.results.value).toEqual([{ id: 'latest' }]);
    }
    finally {
      vi.useRealTimers();
    }
  });

  it('blocks load more during a reset and drops a stale append', async () => {
    const { search, performSearch } = createSearch();
    await search.performSearch();
    let resolveMore!: (value: ProcessedSearchResult<BaseFacet>) => void;
    let resolveReset!: (value: ProcessedSearchResult<BaseFacet>) => void;
    performSearch
      .mockImplementationOnce(() => new Promise(resolve => { resolveMore = resolve; }))
      .mockImplementationOnce(() => new Promise(resolve => { resolveReset = resolve; }));
    search.loadMore();
    search.search('reset', true);
    search.loadMore();
    expect(performSearch).toHaveBeenCalledTimes(3);

    resolveReset(response('reset'));
    await flushPromises();
    resolveMore(response('stale-more'));
    await flushPromises();

    expect(search.results.value).toEqual([{ id: 'reset' }]);
  });
});
