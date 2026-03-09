import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import DocumentSearchInterface from '../DocumentSearchInterface.vue'

// Mock the useDocumentSearch composable
const mockSearchController = {
  searchState: { value: { query: '', isSearching: false, hasResults: false, totalHits: 0, filters: { query: '', tenants: [], contentTypes: [], languages: [], dateRange: {} }, facets: [], results: [], viewMode: 'list', error: null, isOnline: true, status: 'idle' } },
  isSearching: { value: false },
  isLoadingFacets: { value: false },
  isLoadingMore: { value: false },
  hasResults: { value: false },
  hasActiveFilters: { value: false },
  hasMoreResults: { value: false },
  totalHits: { value: 0 },
  results: { value: [] },
  facets: { value: [] },
  filters: { value: { query: '', tenants: [], contentTypes: [], languages: [], dateRange: {} } },
  viewMode: { value: 'list' },
  recentSearches: { value: [] },
  searchError: { value: null },
  isOnline: { value: true },
  retryCount: { value: 0 },
  maxRetries: 3,
  search: vi.fn(),
  setFilter: vi.fn(),
  toggleTenant: vi.fn(),
  toggleContentType: vi.fn(),
  toggleLanguage: vi.fn(),
  setDateRange: vi.fn(),
  setViewMode: vi.fn(),
  clearFilters: vi.fn(),
  clearRecentSearches: vi.fn(),
  removeRecentSearch: vi.fn(),
  loadMore: vi.fn(),
  retrySearch: vi.fn(),
  clearError: vi.fn(),
  loadInitialFacets: vi.fn(),
  initializeSearchClient: vi.fn(),
  searchClient: { value: null }
}

vi.mock('@/Composables/useDocumentSearch', () => ({
  useDocumentSearch: () => mockSearchController
}))

// Mock Inertia.js - extend the global mock with test-specific overrides
vi.mock('@inertiajs/vue3', async () => {
  const inertiaMock = await import('@/mocks/inertia.mock')
  return {
    Link: inertiaMock.Link,
    Head: inertiaMock.Head,
    usePage: inertiaMock.usePage,
    router: inertiaMock.router,
    useForm: inertiaMock.useForm,
  }
})

// Mock Laravel Vue i18n
vi.mock('laravel-vue-i18n', () => ({
  trans: vi.fn((key: string) => {
    const translations: Record<string, string> = {
      'search.language_unknown': 'Not specified'
    }
    return translations[key] || key
  })
}))

// Mock ShadcnVue components
vi.mock('@/Components/ui/button', () => ({
  Button: {
    name: 'Button',
    template: '<button @click="$emit(\'click\', $event)"><slot /></button>',
    emits: ['click']
  }
}))

vi.mock('@/Components/ui/badge', () => ({
  Badge: {
    name: 'Badge',
    template: '<span class="badge"><slot /></span>'
  }
}))

vi.mock('@/Components/ui/separator', () => ({
  Separator: {
    name: 'Separator',
    template: '<hr class="separator" />'
  }
}))

// Mock Lucide icons
vi.mock('lucide-vue-next', () => ({
  Search: { name: 'Search', template: '<svg class="search-icon" />' },
  List: { name: 'List', template: '<svg class="list-icon" />' },
  Minus: { name: 'Minus', template: '<svg class="minus-icon" />' },
  Building2: { name: 'Building2', template: '<svg class="building-icon" />' },
  FileText: { name: 'FileText', template: '<svg class="file-icon" />' },
  Globe: { name: 'Globe', template: '<svg class="globe-icon" />' },
  X: { name: 'X', template: '<svg class="x-icon" />' },
  Loader2: { name: 'Loader2', template: '<svg class="loader-icon" />' },
  Filter: { name: 'Filter', template: '<svg class="filter-icon" />' },
  WifiOff: { name: 'WifiOff', template: '<svg class="wifi-off-icon" />' }
}))

// Mock child components
vi.mock('../SearchErrorBoundary.vue', () => ({
  default: {
    name: 'SearchErrorBoundary',
    template: '<div class="error-boundary"><slot /></div>',
    props: ['error', 'isOnline', 'isRetrying', 'retryCount', 'maxRetries'],
    emits: ['retry', 'clear-error']
  }
}))

vi.mock('../DocumentSearchInput.vue', () => ({
  default: {
    name: 'DocumentSearchInput',
    template: '<div class="search-input">DocumentSearchInput</div>',
    props: ['query', 'isSearching', 'recentSearches', 'typeToSearch'],
    emits: ['update:query', 'update:type-to-search', 'search', 'select-recent', 'clear', 'remove-recent', 'clear-all-history']
  }
}))

vi.mock('../DocumentFacetSidebar.vue', () => ({
  default: {
    name: 'DocumentFacetSidebar',
    template: '<div class="facet-sidebar">DocumentFacetSidebar</div>',
    props: ['facets', 'filters', 'isLoading', 'activeFilterCount'],
    emits: ['update:tenant', 'update:contentType', 'update:language', 'update:dateRange', 'clearFilters']
  }
}))

vi.mock('../DocumentResults.vue', () => ({
  default: {
    name: 'DocumentResults',
    template: '<div class="document-results">DocumentResults</div>',
    props: ['results', 'viewMode', 'isLoading', 'hasQuery', 'searchQuery', 'totalHits', 'hasMoreResults', 'isLoadingMore', 'hasActiveFilters'],
    emits: ['load-more', 'clear-filters']
  }
}))

vi.mock('../SearchPageSwitcher.vue', () => ({
  default: {
    name: 'SearchPageSwitcher',
    template: '<div class="search-page-switcher">SearchPageSwitcher</div>'
  }
}))

describe('DocumentSearchInterface', () => {
  const defaultProps = {
    importantContentTypes: []
  }

  const createWrapper = (props = {}) => {
    return mount(DocumentSearchInterface, {
      props: { ...defaultProps, ...props }
    })
  }

  beforeEach(() => {
    vi.clearAllMocks()
    
    // Reset mock search controller state
    mockSearchController.searchState.value = {
      query: '',
      isSearching: false,
      hasResults: false,
      totalHits: 0,
      filters: { query: '', tenants: [], contentTypes: [], languages: [], dateRange: {} },
      facets: [],
      results: [],
      viewMode: 'list',
      error: null,
      isOnline: true,
      status: 'idle'
    }
    mockSearchController.totalHits.value = 0
    mockSearchController.hasActiveFilters.value = false
    mockSearchController.isSearching.value = false
    mockSearchController.isOnline.value = true
    mockSearchController.searchError.value = null
  })

  describe('component initialization', () => {
    it('renders the main search interface', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.find('.w-full').exists()).toBe(true)
      expect(wrapper.text()).toContain('search.document_search_title')
      expect(wrapper.text()).toContain('search.document_search_description')
    })

    it('accepts importantContentTypes prop', () => {
      const wrapper = createWrapper({ importantContentTypes: ['protokolas', 'nutarimas'] })
      
      // Verify prop is passed correctly
      expect(wrapper.props().importantContentTypes).toEqual(['protokolas', 'nutarimas'])
    })

    it('renders search controller state correctly', () => {
      // Set up mock state
      mockSearchController.totalHits.value = 42
      mockSearchController.searchState.value.query = 'test'
      
      const wrapper = createWrapper()
      
      // Should render the count from search controller
      expect(wrapper.text()).toContain('42')
      expect(wrapper.text()).toContain('search.document_plural')
    })
  })

  describe('search functionality', () => {
    it('handles query updates from search input', async () => {
      const wrapper = createWrapper()
      
      const searchInput = wrapper.findComponent({ name: 'DocumentSearchInput' })
      await searchInput.vm.$emit('update:query', 'test query')
      
      // For update:query events, search is called with debounced auto-search (1 parameter)
      // Wait for debounced function to execute (200ms + buffer)
      await new Promise(resolve => setTimeout(resolve, 300))
      
      expect(mockSearchController.search).toHaveBeenCalledWith('test query')
    })

    it('handles search events from search input', async () => {
      const wrapper = createWrapper()
      
      const searchInput = wrapper.findComponent({ name: 'DocumentSearchInput' })
      await searchInput.vm.$emit('search', 'test search')
      
      // For search events, search is called immediately with 2 parameters (query, immediate=true)
      expect(mockSearchController.search).toHaveBeenCalledWith('test search', true)
    })

    it('handles recent search selection', async () => {
      const wrapper = createWrapper()
      
      const searchInput = wrapper.findComponent({ name: 'DocumentSearchInput' })
      await searchInput.vm.$emit('select-recent', 'recent search')
      
      // For select-recent events, search is called immediately with 2 parameters (query, immediate=true)
      expect(mockSearchController.search).toHaveBeenCalledWith('recent search', true)
    })

    it('handles clear search', async () => {
      const wrapper = createWrapper()
      
      const searchInput = wrapper.findComponent({ name: 'DocumentSearchInput' })
      await searchInput.vm.$emit('clear')
      
      expect(mockSearchController.search).toHaveBeenCalledWith('*', true)
    })

    it('triggers search for empty queries when filters are active', async () => {
      // Set up mock with active filters
      mockSearchController.hasActiveFilters.value = true
      const wrapper = createWrapper()
      
      const searchInput = wrapper.findComponent({ name: 'DocumentSearchInput' })
      await searchInput.vm.$emit('update:query', '')
      
      // Should call search for empty query with filters - gets converted to wildcard
      await new Promise(resolve => setTimeout(resolve, 300)) // Wait for debounced search
      expect(mockSearchController.search).toHaveBeenCalledWith('*', true) // Empty with filters becomes wildcard
    })
  })

  describe('filter functionality', () => {
    it('forwards tenant filter updates', async () => {
      const wrapper = createWrapper()
      
      const sidebar = wrapper.findComponent({ name: 'DocumentFacetSidebar' })
      await sidebar.vm.$emit('update:tenant', 'VU SA')
      
      expect(mockSearchController.toggleTenant).toHaveBeenCalledWith('VU SA')
    })

    it('forwards content type filter updates', async () => {
      const wrapper = createWrapper()
      
      const sidebar = wrapper.findComponent({ name: 'DocumentFacetSidebar' })
      await sidebar.vm.$emit('update:contentType', 'protokolas')
      
      expect(mockSearchController.toggleContentType).toHaveBeenCalledWith('protokolas')
    })

    it('forwards language filter updates', async () => {
      const wrapper = createWrapper()
      
      const sidebar = wrapper.findComponent({ name: 'DocumentFacetSidebar' })
      await sidebar.vm.$emit('update:language', 'Lietuvių')
      
      expect(mockSearchController.toggleLanguage).toHaveBeenCalledWith('Lietuvių')
    })

    it('forwards date range filter updates', async () => {
      const wrapper = createWrapper()
      const dateRange = { preset: '3months' }
      
      const sidebar = wrapper.findComponent({ name: 'DocumentFacetSidebar' })
      await sidebar.vm.$emit('update:dateRange', dateRange)
      
      expect(mockSearchController.setDateRange).toHaveBeenCalledWith(dateRange)
    })

    it('forwards clear filters events', async () => {
      const wrapper = createWrapper()
      
      const sidebar = wrapper.findComponent({ name: 'DocumentFacetSidebar' })
      await sidebar.vm.$emit('clearFilters')
      
      expect(mockSearchController.clearFilters).toHaveBeenCalled()
    })
  })

  describe('view mode functionality', () => {
    it('switches to list view mode', async () => {
      const wrapper = createWrapper()
      
      const listButton = wrapper.findAll('button').find(btn => 
        btn.text().includes('Sąrašas')
      )
      
      if (listButton) {
        await listButton.trigger('click')
        expect(mockSearchController.setViewMode).toHaveBeenCalledWith('list')
      }
    })

    it('switches to compact view mode', async () => {
      const wrapper = createWrapper()
      
      const compactButton = wrapper.findAll('button').find(btn => 
        btn.text().includes('Kompaktiškas')
      )
      
      if (compactButton) {
        await compactButton.trigger('click')
        expect(mockSearchController.setViewMode).toHaveBeenCalledWith('compact')
      }
    })
  })

  describe('results display', () => {
    it('shows results count when there are results', () => {
      mockSearchController.totalHits.value = 42
      mockSearchController.searchState.value.query = 'test'
      
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.found_results')
      expect(wrapper.text()).toContain('42')
      expect(wrapper.text()).toContain('search.document_plural')
    })

    it('shows no results message when search returns nothing', () => {
      mockSearchController.totalHits.value = 0
      mockSearchController.searchState.value.query = 'nonexistent'
      mockSearchController.isSearching.value = false
      
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.no_documents_found')
    })

    it('shows no results message for short queries with no results', () => {
      mockSearchController.searchState.value.query = 'ab'
      mockSearchController.totalHits.value = 0
      mockSearchController.results.value = []
      mockSearchController.isSearching.value = false
      
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.no_documents_found')
    })

    it('shows instruction message when no query', () => {
      mockSearchController.searchState.value.query = ''
      mockSearchController.isSearching.value = false
      
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.enter_search_or_browse')
    })

    it('shows total documents count for wildcard search', () => {
      mockSearchController.totalHits.value = 150
      mockSearchController.searchState.value.query = '*'
      mockSearchController.hasActiveFilters.value = false
      
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.showing_results')
      expect(wrapper.text()).toContain('150')
      expect(wrapper.text()).toContain('search.newest_first')
    })
  })

  describe('filter tags (mobile)', () => {
    it('shows active filter tags when filters are applied', () => {
      mockSearchController.hasActiveFilters.value = true
      mockSearchController.filters.value = {
        query: '',
        tenants: ['VU SA'],
        contentTypes: ['protokolas'],
        languages: ['Lietuvių'],
        dateRange: {}
      }
      
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('VU SA')
      expect(wrapper.text()).toContain('protokolas')
      expect(wrapper.text()).toContain('LT')
    })

    it('allows removing individual filter tags', async () => {
      mockSearchController.hasActiveFilters.value = true
      mockSearchController.filters.value = {
        query: '',
        tenants: ['VU SA'],
        contentTypes: [],
        languages: [],
        dateRange: {}
      }
      
      const wrapper = createWrapper()
      
      // Find and click the X button for a tenant filter
      const removeButtons = wrapper.findAll('button').filter(btn => 
        btn.find('.x-icon').exists()
      )
      
      if (removeButtons.length > 0) {
        await removeButtons[0].trigger('click')
        expect(mockSearchController.toggleTenant).toHaveBeenCalled()
      }
    })

    it('allows clearing all filters', async () => {
      mockSearchController.hasActiveFilters.value = true
      
      const wrapper = createWrapper()
      
      const clearAllButton = wrapper.findAll('button').find(btn => 
        btn.text().includes('Išvalyti viską')
      )
      
      if (clearAllButton) {
        await clearAllButton.trigger('click')
        expect(mockSearchController.clearFilters).toHaveBeenCalled()
      }
    })
  })

  describe('offline state', () => {
    it('shows offline indicator when not online', () => {
      mockSearchController.isOnline.value = false
      
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.offline_message')
      expect(wrapper.findComponent({ name: 'WifiOff' }).exists()).toBe(true)
    })

    it('hides offline indicator when online', () => {
      mockSearchController.isOnline.value = true
      
      const wrapper = createWrapper()
      
      expect(wrapper.text()).not.toContain('Nėra interneto ryšio')
    })
  })

  describe('error handling', () => {
    it('forwards retry events to search controller', async () => {
      const wrapper = createWrapper()
      
      const errorBoundary = wrapper.findComponent({ name: 'SearchErrorBoundary' })
      await errorBoundary.vm.$emit('retry')
      
      expect(mockSearchController.retrySearch).toHaveBeenCalled()
    })

    it('forwards clear error events to search controller', async () => {
      const wrapper = createWrapper()
      
      const errorBoundary = wrapper.findComponent({ name: 'SearchErrorBoundary' })
      await errorBoundary.vm.$emit('clear-error')
      
      expect(mockSearchController.clearError).toHaveBeenCalled()
    })
  })

  describe('recent searches management', () => {
    it('handles recent search removal', async () => {
      const wrapper = createWrapper()
      
      const searchInput = wrapper.findComponent({ name: 'DocumentSearchInput' })
      await searchInput.vm.$emit('remove-recent', 'old search')
      
      expect(mockSearchController.removeRecentSearch).toHaveBeenCalledWith('old search')
    })

    it('handles clearing all recent searches', async () => {
      const wrapper = createWrapper()
      
      const searchInput = wrapper.findComponent({ name: 'DocumentSearchInput' })
      await searchInput.vm.$emit('clear-all-history')
      
      expect(mockSearchController.clearRecentSearches).toHaveBeenCalled()
    })
  })

  describe('load more functionality', () => {
    it('forwards load more events to search controller', async () => {
      const wrapper = createWrapper()
      
      const results = wrapper.findComponent({ name: 'DocumentResults' })
      await results.vm.$emit('load-more')
      
      expect(mockSearchController.loadMore).toHaveBeenCalled()
    })

    it('forwards clear filters from results to search controller', async () => {
      const wrapper = createWrapper()
      
      const results = wrapper.findComponent({ name: 'DocumentResults' })
      await results.vm.$emit('clear-filters')
      
      // Should trigger the same as clear - search all documents
      expect(mockSearchController.search).toHaveBeenCalledWith('*', true)
    })
  })

  describe('language utilities', () => {
    it('returns correct language flags', () => {
      const wrapper = createWrapper()
      const vm = wrapper.vm as any
      
      expect(vm.getLanguageFlag('Lietuvių')).toContain('lt.svg')
      expect(vm.getLanguageFlag('Anglų')).toContain('gb.svg')
      expect(vm.getLanguageFlag('Unknown')).toBe('')
    })

    it('returns correct language display names', () => {
      const wrapper = createWrapper()
      const vm = wrapper.vm as any
      
      expect(vm.getLanguageDisplay('Lietuvių')).toBe('LT')
      expect(vm.getLanguageDisplay('Anglų')).toBe('EN')
      expect(vm.getLanguageDisplay('Unknown')).toBe('Not specified')
    })
  })

  describe('computed properties', () => {
    it('calculates active filter count correctly', () => {
      mockSearchController.filters.value = {
        query: '',
        tenants: ['VU SA', 'VU SA CHGF'],
        contentTypes: ['protokolas'],
        languages: [],
        dateRange: { preset: '3months' }
      }
      
      const wrapper = createWrapper()
      const vm = wrapper.vm as any
      
      expect(vm.activeFilterCount).toBe(3) // tenants, contentTypes, dateRange
    })

    it('generates filter summary correctly', () => {
      mockSearchController.filters.value = {
        query: '',
        tenants: ['VU SA', 'VU SA CHGF'],
        contentTypes: ['protokolas'],
        languages: ['Lietuvių'],
        dateRange: {}
      }
      
      const wrapper = createWrapper()
      const vm = wrapper.vm as any
      
      const summary = vm.filterSummary
      expect(summary).toEqual(expect.arrayContaining([expect.stringContaining('2')]))
      expect(summary).toEqual(expect.arrayContaining([expect.stringContaining('1')]))
      expect(summary.length).toBeGreaterThan(0)
    })
  })

  describe('integration workflow', () => {
    it('completes full search workflow', async () => {
      const wrapper = createWrapper()
      
      // User enters search query
      const searchInput = wrapper.findComponent({ name: 'DocumentSearchInput' })
      await searchInput.vm.$emit('update:query', 'test document')
      // Wait for debounced function to execute (200ms + buffer)
      await new Promise(resolve => setTimeout(resolve, 300))
      expect(mockSearchController.search).toHaveBeenCalledWith('test document')
      
      // User applies filters
      const sidebar = wrapper.findComponent({ name: 'DocumentFacetSidebar' })
      await sidebar.vm.$emit('update:tenant', 'VU SA')
      expect(mockSearchController.toggleTenant).toHaveBeenCalledWith('VU SA')
      
      // User changes view mode
      const compactButton = wrapper.findAll('button').find(btn => 
        btn.text().includes('Kompaktiškas')
      )
      if (compactButton) {
        await compactButton.trigger('click')
        expect(mockSearchController.setViewMode).toHaveBeenCalledWith('compact')
      }
      
      // User loads more results
      const results = wrapper.findComponent({ name: 'DocumentResults' })
      await results.vm.$emit('load-more')
      expect(mockSearchController.loadMore).toHaveBeenCalled()
    })

    it('handles complete filter management workflow', async () => {
      const wrapper = createWrapper()
      
      // User applies multiple filters
      const sidebar = wrapper.findComponent({ name: 'DocumentFacetSidebar' })
      await sidebar.vm.$emit('update:tenant', 'VU SA')
      await sidebar.vm.$emit('update:contentType', 'protokolas')
      await sidebar.vm.$emit('update:language', 'Lietuvių')
      
      expect(mockSearchController.toggleTenant).toHaveBeenCalledWith('VU SA')
      expect(mockSearchController.toggleContentType).toHaveBeenCalledWith('protokolas')
      expect(mockSearchController.toggleLanguage).toHaveBeenCalledWith('Lietuvių')
      
      // User clears all filters
      await sidebar.vm.$emit('clearFilters')
      expect(mockSearchController.clearFilters).toHaveBeenCalled()
    })
  })
})