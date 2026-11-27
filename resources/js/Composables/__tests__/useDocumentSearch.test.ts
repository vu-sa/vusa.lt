/**
 * Tests for useDocumentSearch composable (refactored version)
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { trackEvent } from '@/Plugins/umami';

vi.mock('@/Plugins/umami', () => ({ trackEvent: vi.fn() }));

// Mock dependencies
vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@vueuse/core')>();
  return {
    ...actual,
    useLocalStorage: vi.fn((key: string, defaultValue: Record<string, unknown>) => {
      return { value: { ...defaultValue } };
    }),
    useOnline: vi.fn(() => ({ value: true })),
  };
});

vi.mock('@/Search/public/DocumentSearchService', () => ({
  // The composable constructs the service with `new`, so this must stay a `function`, not an arrow.
  // eslint-disable-next-line prefer-arrow-callback
  DocumentSearchService: vi.fn().mockImplementation(function () {
    return {
      performSearch: vi.fn(() => Promise.resolve({
        hits: [],
        totalHits: 0,
        totalPages: 0,
        currentPage: 0,
        facets: [],
      })),
      loadInitialFacets: vi.fn(() => Promise.resolve([])),
      cancelCurrentSearch: vi.fn(),
    };
  }),
}));

describe('useDocumentSearch (refactored)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        typesenseConfig: {
          apiKey: 'test-key',
          nodes: [{ protocol: 'http', host: 'localhost', port: 8108 }],
          collections: {
            documents: 'documents',
          },
        },
      }),
    );
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  describe('initialization', () => {
    it('creates default filters correctly', async () => {
      const { useDocumentSearch } = await import('../useDocumentSearch');
      const controller = useDocumentSearch();

      expect(controller.filters.value).toEqual({
        query: '',
        tenants: [],
        contentTypes: [],
        languages: [],
        dateRange: {},
        sort: 'relevance',
        effectStatuses: ['true', 'unknown'],
      });
    });
  });

  describe('sorting', () => {
    it('browses newest first and switches to relevance once a query is entered', async () => {
      const { useDocumentSearch } = await import('../useDocumentSearch');
      const controller = useDocumentSearch();
      expect(controller.activeSort.value).toBe('date_desc');
      controller.setSortBy('date_desc');
      expect(controller.filters.value.sort).toBe('relevance');
      controller.filters.value.query = 'įstatai';
      expect(controller.activeSort.value).toBe('relevance');
    });

    it('keeps an explicit date choice once a query exists', async () => {
      const { useDocumentSearch } = await import('../useDocumentSearch');
      const controller = useDocumentSearch();
      controller.setSortBy('date_asc');
      controller.filters.value.query = 'įstatai';
      expect(controller.activeSort.value).toBe('date_asc');
      controller.setSortBy('date_desc');
      expect(controller.activeSort.value).toBe('date_desc');
    });
  });

  describe('search term analytics', () => {
    it('reports a settled document search, and not the browse wildcard', async () => {
      vi.useFakeTimers();
      const { useDocumentSearch } = await import('../useDocumentSearch');
      const controller = useDocumentSearch();
      expect(await controller.initializeSearchClient()).not.toBeNull();

      controller.search('*', true);
      await vi.advanceTimersByTimeAsync(2500);
      expect(trackEvent).not.toHaveBeenCalled();

      controller.search('Stipendija', true);
      await vi.advanceTimersByTimeAsync(2500);

      expect(trackEvent).toHaveBeenCalledTimes(1);
      expect(trackEvent).toHaveBeenCalledWith('search_submitted', {
        term: 'stipendija',
        results: 0,
        source: 'documents',
      });
    });
  });
});
