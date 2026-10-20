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

vi.mock('../useSearchClient', () => ({
  createTypesenseClients: vi.fn(() => ({
    searchClient: { search: vi.fn() },
    typesenseClient: {
      search: vi.fn(() => Promise.resolve({
        hits: [],
        found: 0,
        facet_counts: [],
      })),
    },
  })),
}));

vi.mock('../../Services/DocumentSearchService', () => ({
  // The composable constructs the service with `new`.
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
          host: 'localhost',
          port: 8108,
          protocol: 'http',
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

    it('provides all required controller methods', async () => {
      const { useDocumentSearch } = await import('../useDocumentSearch');
      const controller = useDocumentSearch();

      // Core methods
      expect(typeof controller.search).toBe('function');
      expect(typeof controller.setFilter).toBe('function');
      expect(typeof controller.loadMore).toBe('function');
      expect(typeof controller.retrySearch).toBe('function');
      expect(typeof controller.clearError).toBe('function');
      expect(typeof controller.loadInitialFacets).toBe('function');

      // Document-specific methods
      expect(typeof controller.toggleTenant).toBe('function');
      expect(typeof controller.toggleContentType).toBe('function');
      expect(typeof controller.toggleLanguage).toBe('function');
      expect(typeof controller.setDateRange).toBe('function');
      expect(typeof controller.setViewMode).toBe('function');
      expect(typeof controller.clearFilters).toBe('function');
      expect(typeof controller.clearRecentSearches).toBe('function');
      expect(typeof controller.removeRecentSearch).toBe('function');

      // Initialization
      expect(typeof controller.initializeSearchClient).toBe('function');
    });

    it('provides all required state refs', async () => {
      const { useDocumentSearch } = await import('../useDocumentSearch');
      const controller = useDocumentSearch();

      // State refs
      expect(controller.isSearching).toBeDefined();
      expect(controller.isLoadingFacets).toBeDefined();
      expect(controller.isLoadingMore).toBeDefined();
      expect(controller.hasResults).toBeDefined();
      expect(controller.hasActiveFilters).toBeDefined();
      expect(controller.hasMoreResults).toBeDefined();
      expect(controller.totalHits).toBeDefined();
      expect(controller.results).toBeDefined();
      expect(controller.facets).toBeDefined();
      expect(controller.filters).toBeDefined();
      expect(controller.viewMode).toBeDefined();
      expect(controller.recentSearches).toBeDefined();

      // Error handling
      expect(controller.searchError).toBeDefined();
      expect(controller.isOnline).toBeDefined();
      expect(controller.retryCount).toBeDefined();
      expect(controller.maxRetries).toBe(3);
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

  describe('searchState computed', () => {
    it('computes searchState correctly', async () => {
      const { useDocumentSearch } = await import('../useDocumentSearch');
      const controller = useDocumentSearch();

      const state = controller.searchState.value;

      expect(state).toHaveProperty('status');
      expect(state).toHaveProperty('isSearching');
      expect(state).toHaveProperty('hasResults');
      expect(state).toHaveProperty('totalHits');
      expect(state).toHaveProperty('query');
      expect(state).toHaveProperty('filters');
      expect(state).toHaveProperty('facets');
      expect(state).toHaveProperty('results');
      expect(state).toHaveProperty('viewMode');
      expect(state).toHaveProperty('error');
      expect(state).toHaveProperty('isOnline');
    });
  });
});
