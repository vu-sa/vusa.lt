/**
 * Tests for useSearchInterface composable
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { nextTick, ref } from 'vue';

import { useSearchInterface } from '../useSearchInterface';

// Mock lodash-es debounce to execute immediately in tests
vi.mock('lodash-es', () => ({
  debounce: vi.fn((fn: Function) => {
    const debouncedFn = (...args: unknown[]) => fn(...args);
    debouncedFn.cancel = vi.fn();
    return debouncedFn;
  }),
}));

// Create a mock search controller using Vue refs so watchers work
function createMockController() {
  const filters = ref({ query: '', tenants: [], contentTypes: [], languages: [], dateRange: {} });
  const searchState = ref({ query: '' });

  return {
    search: vi.fn(),
    clearFilters: vi.fn(),
    cancelPendingSearch: vi.fn(),
    removeRecentSearch: vi.fn(),
    clearRecentSearches: vi.fn(),
    filters,
    searchState,
  };
}

describe('useSearchInterface', () => {
  let mockController: ReturnType<typeof createMockController>;

  beforeEach(() => {
    mockController = createMockController();
    vi.clearAllMocks();
  });

  describe('initialization', () => {
    it('returns required refs and handlers', () => {
      const result = useSearchInterface({ searchController: mockController as any });

      expect(result.inputQuery.value).toBe('');
      expect(result.typeToSearch.value).toBe(true);
      expect(result.hasActiveFilters.value).toBe(false);
      expect(result.activeFilterCount.value).toBe(0);

      expect(typeof result.handleQueryUpdate).toBe('function');
      expect(typeof result.handleSearch).toBe('function');
      expect(typeof result.handleSelectRecent).toBe('function');
      expect(typeof result.handleClear).toBe('function');
      expect(typeof result.handleTypeToSearchUpdate).toBe('function');
      expect(typeof result.handleRemoveRecent).toBe('function');
      expect(typeof result.handleClearAllHistory).toBe('function');
    });
  });

  describe('handleQueryUpdate', () => {
    it('updates inputQuery and triggers debounced search for non-empty query', () => {
      const { handleQueryUpdate, inputQuery } = useSearchInterface({ searchController: mockController as any });

      handleQueryUpdate('test query');

      expect(inputQuery.value).toBe('test query');
      expect(mockController.search).toHaveBeenCalledWith('test query');
    });

    it('searches wildcard when query is cleared', () => {
      const { handleQueryUpdate, inputQuery } = useSearchInterface({ searchController: mockController as any });

      handleQueryUpdate('');

      expect(inputQuery.value).toBe('');
      expect(mockController.search).toHaveBeenCalledWith('*', true);
    });
  });

  describe('handleSearch', () => {
    it('searches immediately with provided query', () => {
      const { handleSearch } = useSearchInterface({ searchController: mockController as any });

      handleSearch('direct search');

      expect(mockController.search).toHaveBeenCalledWith('direct search', true);
    });

    it('falls back to inputQuery when no query provided', () => {
      const { handleSearch, inputQuery } = useSearchInterface({ searchController: mockController as any });
      inputQuery.value = 'existing query';

      handleSearch();

      expect(mockController.search).toHaveBeenCalledWith('existing query', true);
    });

    it('uses wildcard for empty query', () => {
      const { handleSearch } = useSearchInterface({ searchController: mockController as any });

      handleSearch('');

      expect(mockController.search).toHaveBeenCalledWith('*', true);
    });
  });

  describe('handleSelectRecent', () => {
    it('searches selected recent search immediately', () => {
      const { handleSelectRecent, inputQuery } = useSearchInterface({ searchController: mockController as any });

      handleSelectRecent('recent item');

      expect(inputQuery.value).toBe('recent item');
      expect(mockController.search).toHaveBeenCalledWith('recent item', true);
    });
  });

  describe('handleClear', () => {
    it('clears filters and searches wildcard', () => {
      const { handleClear, inputQuery } = useSearchInterface({ searchController: mockController as any });
      inputQuery.value = 'some query';

      handleClear();

      expect(inputQuery.value).toBe('');
      expect(mockController.clearFilters).toHaveBeenCalled();
      expect(mockController.cancelPendingSearch).toHaveBeenCalled();
      expect(mockController.search).toHaveBeenCalledWith('*', true);
    });
  });

  describe('handleRemoveRecent', () => {
    it('delegates to controller removeRecentSearch', () => {
      const { handleRemoveRecent } = useSearchInterface({ searchController: mockController as any });

      handleRemoveRecent('old search');

      expect(mockController.removeRecentSearch).toHaveBeenCalledWith('old search');
    });
  });

  describe('handleClearAllHistory', () => {
    it('delegates to controller clearRecentSearches', () => {
      const { handleClearAllHistory } = useSearchInterface({ searchController: mockController as any });

      handleClearAllHistory();

      expect(mockController.clearRecentSearches).toHaveBeenCalled();
    });
  });

  describe('handleTypeToSearchUpdate', () => {
    it('toggles typeToSearch state', () => {
      const { handleTypeToSearchUpdate, typeToSearch } = useSearchInterface({ searchController: mockController as any });

      expect(typeToSearch.value).toBe(true);
      handleTypeToSearchUpdate(false);
      expect(typeToSearch.value).toBe(false);
    });
  });

  describe('query sync watcher', () => {
    it('syncs inputQuery with controller query updates', async () => {
      const { inputQuery } = useSearchInterface({ searchController: mockController as any });

      mockController.searchState.value.query = 'updated query';
      await nextTick();

      expect(inputQuery.value).toBe('updated query');
    });

    it('sets inputQuery to empty string when controller query is wildcard', async () => {
      const { inputQuery } = useSearchInterface({ searchController: mockController as any });

      mockController.searchState.value.query = '*';
      await nextTick();

      expect(inputQuery.value).toBe('');
    });
  });

  describe('hasActiveFilters', () => {
    it('reflects active filters from controller', () => {
      (mockController.filters.value as any) = {
        query: '',
        tenants: ['VU SA'],
        contentTypes: [],
        languages: [],
        dateRange: {},
      };

      const { hasActiveFilters } = useSearchInterface({ searchController: mockController as any });

      expect(hasActiveFilters.value).toBe(true);
    });

    it('ignores query when computing active filters', () => {
      (mockController.filters.value as any) = {
        query: 'test',
        tenants: [],
        contentTypes: [],
        languages: [],
        dateRange: {},
      };

      const { hasActiveFilters } = useSearchInterface({ searchController: mockController as any });

      expect(hasActiveFilters.value).toBe(false);
    });

    it('treats recent preset as not active', () => {
      (mockController.filters.value as any) = {
        query: '',
        tenants: [],
        contentTypes: [],
        languages: [],
        dateRange: { preset: 'recent' },
      };

      const { hasActiveFilters } = useSearchInterface({ searchController: mockController as any });

      expect(hasActiveFilters.value).toBe(false);
    });
  });

  describe('activeFilterCount', () => {
    it('counts active filter categories', () => {
      (mockController.filters.value as any) = {
        query: '',
        tenants: ['VU SA'],
        contentTypes: ['protokolas'],
        languages: [],
        dateRange: {},
      };

      const { activeFilterCount } = useSearchInterface({ searchController: mockController as any });

      expect(activeFilterCount.value).toBe(2);
    });
  });
});
