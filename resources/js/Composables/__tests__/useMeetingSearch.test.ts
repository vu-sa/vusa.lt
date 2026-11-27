/**
 * Tests for useMeetingSearch composable (refactored version)
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';

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

vi.mock('@/Search/public/MeetingSearchService', () => ({
  // eslint-disable-next-line prefer-arrow-callback
  MeetingSearchService: vi.fn().mockImplementation(function () {
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

describe('useMeetingSearch (refactored)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        typesenseConfig: {
          apiKey: 'test-key',
          nodes: [{ protocol: 'http', host: 'localhost', port: 8108 }],
          collections: {
            public_meetings: 'public_meetings',
          },
        },
      }),
    );
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('initialization', () => {
    it('creates default filters correctly', async () => {
      const { useMeetingSearch } = await import('../useMeetingSearch');
      const controller = useMeetingSearch();

      expect(controller.filters.value).toEqual({
        query: '',
        tenants: [],
        institutionTypes: [],
        years: [],
        successRateRanges: [],
        dateRange: {},
        sort: 'relevance',
      });
    });
  });
});
