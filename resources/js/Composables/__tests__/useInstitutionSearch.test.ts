/**
 * Tests for useInstitutionSearch composable (refactored version)
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

vi.mock('@/Search/public/InstitutionSearchService', () => ({
  // eslint-disable-next-line prefer-arrow-callback
  InstitutionSearchService: vi.fn().mockImplementation(function () {
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

describe('useInstitutionSearch (refactored)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(
      createMockPage({
        typesenseConfig: {
          apiKey: 'test-key',
          nodes: [{ protocol: 'http', host: 'localhost', port: 8108 }],
          collections: {
            public_institutions: 'public_institutions',
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
      const { useInstitutionSearch } = await import('../useInstitutionSearch');
      const controller = useInstitutionSearch();

      expect(controller.filters.value).toEqual({
        query: '',
        tenants: [],
        types: [],
        hasContacts: null,
      });
    });
  });
});
