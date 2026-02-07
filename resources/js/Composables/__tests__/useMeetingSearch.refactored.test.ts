/**
 * Tests for useMeetingSearch composable (refactored version)
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// Mock dependencies
vi.mock('@vueuse/core', () => ({
  useLocalStorage: vi.fn((key: string, defaultValue: any) => {
    return { value: { ...defaultValue } }
  }),
  useOnline: vi.fn(() => ({ value: true }))
}))

vi.mock('@inertiajs/vue3', () => ({
  usePage: vi.fn(() => ({
    props: {
      app: { locale: 'lt' },
      typesenseConfig: {
        apiKey: 'test-key',
        host: 'localhost',
        port: 8108,
        protocol: 'http',
        collections: {
          public_meetings: 'public_meetings'
        }
      }
    }
  }))
}))

vi.mock('../useSearchClient', () => ({
  createTypesenseClients: vi.fn(() => ({
    searchClient: { search: vi.fn() },
    typesenseClient: {
      search: vi.fn(() => Promise.resolve({
        hits: [],
        found: 0,
        facet_counts: []
      }))
    }
  }))
}))

vi.mock('../../Services/MeetingSearchService', () => ({
  MeetingSearchService: vi.fn().mockImplementation(() => ({
    performSearch: vi.fn(() => Promise.resolve({
      hits: [],
      totalHits: 0,
      totalPages: 0,
      currentPage: 0,
      facets: []
    })),
    loadInitialFacets: vi.fn(() => Promise.resolve([])),
    cancelCurrentSearch: vi.fn()
  }))
}))

describe('useMeetingSearch (refactored)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('initialization', () => {
    it('creates default filters correctly', async () => {
      const { useMeetingSearch } = await import('../useMeetingSearch')
      const controller = useMeetingSearch()

      expect(controller.filters.value).toEqual({
        query: '',
        tenants: [],
        institutionTypes: [],
        years: [],
        successRateRanges: [],
        dateRange: {}
      })
    })

    it('provides all required controller methods', async () => {
      const { useMeetingSearch } = await import('../useMeetingSearch')
      const controller = useMeetingSearch()

      // Core methods
      expect(typeof controller.search).toBe('function')
      expect(typeof controller.setFilter).toBe('function')
      expect(typeof controller.loadMore).toBe('function')
      expect(typeof controller.retrySearch).toBe('function')
      expect(typeof controller.clearError).toBe('function')
      expect(typeof controller.loadInitialFacets).toBe('function')

      // Meeting-specific methods
      expect(typeof controller.toggleTenant).toBe('function')
      expect(typeof controller.toggleInstitutionType).toBe('function')
      expect(typeof controller.toggleYear).toBe('function')
      expect(typeof controller.toggleSuccessRate).toBe('function')
      expect(typeof controller.setDateRange).toBe('function')
      expect(typeof controller.setViewMode).toBe('function')
      expect(typeof controller.clearFilters).toBe('function')
      expect(typeof controller.clearRecentSearches).toBe('function')
      expect(typeof controller.removeRecentSearch).toBe('function')

      // Initialization
      expect(typeof controller.initializeSearchClient).toBe('function')
    })

    it('provides all required state refs', async () => {
      const { useMeetingSearch } = await import('../useMeetingSearch')
      const controller = useMeetingSearch()

      // State refs
      expect(controller.isSearching).toBeDefined()
      expect(controller.isLoadingFacets).toBeDefined()
      expect(controller.isLoadingMore).toBeDefined()
      expect(controller.hasResults).toBeDefined()
      expect(controller.hasActiveFilters).toBeDefined()
      expect(controller.hasMoreResults).toBeDefined()
      expect(controller.totalHits).toBeDefined()
      expect(controller.results).toBeDefined()
      expect(controller.facets).toBeDefined()
      expect(controller.filters).toBeDefined()
      expect(controller.viewMode).toBeDefined()
      expect(controller.recentSearches).toBeDefined()

      // Error handling
      expect(controller.searchError).toBeDefined()
      expect(controller.isOnline).toBeDefined()
      expect(controller.retryCount).toBeDefined()
      expect(controller.maxRetries).toBe(3)
    })
  })

  describe('searchState computed', () => {
    it('computes searchState correctly', async () => {
      const { useMeetingSearch } = await import('../useMeetingSearch')
      const controller = useMeetingSearch()

      const state = controller.searchState.value

      expect(state).toHaveProperty('isSearching')
      expect(state).toHaveProperty('hasResults')
      expect(state).toHaveProperty('totalHits')
      expect(state).toHaveProperty('query')
      expect(state).toHaveProperty('filters')
      expect(state).toHaveProperty('facets')
      expect(state).toHaveProperty('results')
      expect(state).toHaveProperty('viewMode')
      expect(state).toHaveProperty('error')
      expect(state).toHaveProperty('isOnline')
      expect(state).toHaveProperty('status')
    })
  })
})
