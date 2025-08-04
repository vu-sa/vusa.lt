import type { Meta, StoryObj } from '@storybook/vue3-vite'
import { fn, userEvent, within } from 'storybook/test'
import DocumentSearchInterface from './DocumentSearchInterface.vue'
import type { SearchFilters, FacetResult } from '@/Types/DocumentSearchTypes'

// Mock the useDocumentSearch composable
const mockSearchController = {
  // State
  searchState: {
    query: '',
    hits: [],
    totalHits: 0,
    facets: null,
    filters: {
      tenants: [],
      contentTypes: [],
      language: [],
      dateRange: { preset: null, startDate: null, endDate: null }
    },
    pagination: { currentPage: 1, hitsPerPage: 24, hasMore: false },
    viewMode: 'list'
  },
  isSearching: false,
  isLoadingMore: false,
  isLoadingFacets: false,
  searchError: null,
  isOnline: true,
  recentSearches: ['VU SR protokolai', 'studijų programa', 'susirinkimas'],
  
  // Methods
  initializeSearchClient: fn(),
  search: fn(),
  loadInitialFacets: fn(),
  toggleTenant: fn(),
  toggleContentType: fn(),
  toggleLanguage: fn(),
  setDateRange: fn(),
  clearFilters: fn(),
  setViewMode: fn(),
  loadMore: fn(),
  removeRecentSearch: fn(),
  clearRecentSearches: fn(),
  retrySearch: fn(),
  clearError: fn()
}

// Mock facet data
const mockFacets: FacetResult = {
  facets: {
    tenant: {
      stats: { total_values: 5 },
      counts: [
        { value: 'VU SA', count: 156, highlighted: 'VU SA' },
        { value: 'VU IF', count: 89, highlighted: 'VU IF' },
        { value: 'VU MIF', count: 67, highlighted: 'VU MIF' }
      ]
    },
    content_type: {
      stats: { total_values: 4 },
      counts: [
        { value: 'protokolas', count: 198, highlighted: 'protokolas' },
        { value: 'ataskaita', count: 87, highlighted: 'ataskaita' }
      ]
    },
    lang: {
      stats: { total_values: 2 },
      counts: [
        { value: 'Lietuvių', count: 298, highlighted: 'Lietuvių' },
        { value: 'English', count: 86, highlighted: 'English' }
      ]
    }
  }
}

const mockSearchResults = [
  {
    id: '1',
    title: 'VU SA valdybos posėdžio protokolas Nr. 12',
    description: 'Vilniaus universiteto Studentų atstovybės valdybos posėdžio protokolas, kuriame aptarti studijų kokybės klausimai...',
    url: '/documents/vu-sa-protokolas-12',
    tenant: 'VU SA',
    content_type: 'protokolas',
    created_at: '2024-12-01T10:00:00Z',
    lang: 'Lietuvių'
  },
  {
    id: '2', 
    title: 'Studijų programos vertinimo ataskaita 2024',
    description: 'Išsami informatikos studijų programos vertinimo ataskaita, apimanti studentų atsiliepimus ir dėstytojų rekomendacijas...',
    url: '/documents/studiju-programos-ataskaita-2024',
    tenant: 'VU IF',
    content_type: 'ataskaita',
    created_at: '2024-11-28T14:30:00Z',
    lang: 'Lietuvių'
  }
]

const meta: Meta<typeof DocumentSearchInterface> = {
  title: 'Components/Search/DocumentSearchInterface',
  component: DocumentSearchInterface,
  tags: ['autodocs'],
  argTypes: {
    initialQuery: { control: 'text' },
    initialFilters: { control: 'object' },
    allContentTypes: { control: 'object' },
    typesenseConfig: { control: 'object' }
  },
  args: {
    initialQuery: '',
    initialFilters: null,
    allContentTypes: ['protokolas', 'ataskaita', 'sprendimas', 'pranešimas'],
    typesenseConfig: {
      apiKey: 'mock-api-key',
      nodes: [{ host: 'localhost', port: 8108, protocol: 'http' }],
      collectionName: 'documents'
    }
  },
  decorators: [
    (story) => ({
      components: { story },
      template: `
        <div class="min-h-screen bg-gray-50">
          <story />
        </div>
      `
    })
  ],
  parameters: {
    layout: 'fullscreen',
    backgrounds: {
      default: 'light'
    },
    // Mock the useDocumentSearch composable
    mockData: [{
      url: '/api/search*',
      method: 'GET',
      status: 200,
      response: {
        hits: mockSearchResults,
        found: mockSearchResults.length,
        facet_counts: mockFacets.facets
      }
    }]
  }
}

export default meta
type Story = StoryObj<typeof meta>

// Default empty state
export const Default: Story = {
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}

// With initial query
export const WithInitialQuery: Story = {
  args: {
    initialQuery: 'VU SA protokolai'
  },
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}

// With initial filters
export const WithInitialFilters: Story = {
  args: {
    initialFilters: {
      tenants: ['VU SA'],
      contentTypes: ['protokolas'],
      language: ['Lietuvių'],
      dateRange: {
        preset: 'last_month',
        startDate: '2024-11-01',
        endDate: '2024-11-30'
      }
    }
  },
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}

// Loading state simulation
export const LoadingState: Story = {
  decorators: [
    (story) => ({
      components: { story },
      setup() {
        // Mock loading state
        mockSearchController.isSearching = true
        mockSearchController.isLoadingFacets = true
        return {}
      },
      template: `
        <div class="min-h-screen bg-gray-50">
          <story />
        </div>
      `
    })
  ],
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}

// With search results
export const WithResults: Story = {
  decorators: [
    (story) => ({
      components: { story },
      setup() {
        // Mock results state
        mockSearchController.searchState.query = 'protokolai'
        mockSearchController.searchState.hits = mockSearchResults
        mockSearchController.searchState.totalHits = 156
        mockSearchController.searchState.facets = mockFacets
        mockSearchController.isSearching = false
        return {}
      },
      template: `
        <div class="min-h-screen bg-gray-50">
          <story />
        </div>
      `
    })
  ],
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}

// No results state
export const NoResults: Story = {
  decorators: [
    (story) => ({
      components: { story },
      setup() {
        // Mock no results state
        mockSearchController.searchState.query = 'nonexistent query'
        mockSearchController.searchState.hits = []
        mockSearchController.searchState.totalHits = 0
        mockSearchController.searchState.facets = {
          facets: {
            tenant: { stats: { total_values: 0 }, counts: [] },
            content_type: { stats: { total_values: 0 }, counts: [] },
            lang: { stats: { total_values: 0 }, counts: [] }
          }
        }
        mockSearchController.isSearching = false
        return {}
      },
      template: `
        <div class="min-h-screen bg-gray-50">
          <story />
        </div>
      `
    })
  ],
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}

// Error state
export const ErrorState: Story = {
  decorators: [
    (story) => ({
      components: { story },
      setup() {
        // Mock error state
        mockSearchController.searchError = {
          type: 'network',
          message: 'Failed to connect to search service'
        }
        mockSearchController.isSearching = false
        return {}
      },
      template: `
        <div class="min-h-screen bg-gray-50">
          <story />
        </div>
      `
    })
  ],
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}

// Offline state
export const OfflineState: Story = {
  decorators: [
    (story) => ({
      components: { story },
      setup() {
        // Mock offline state
        mockSearchController.isOnline = false
        return {}
      },
      template: `
        <div class="min-h-screen bg-gray-50">
          <story />
        </div>
      `
    })
  ],
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}

// Mobile view
export const MobileView: Story = {
  parameters: {
    viewport: {
      defaultViewport: 'mobile1'
    }
  },
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}

// Interactive demo
export const InteractiveDemo: Story = {
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  }),
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement)
    
    // Wait for component to initialize
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    try {
      // Find and interact with search input
      const searchInput = canvas.getByPlaceholderText(/ieškokite/i)
      await userEvent.click(searchInput)
      await userEvent.type(searchInput, 'VU SA protokolai')
      
      // Press Enter to search
      await userEvent.keyboard('{Enter}')
      
      // Wait for search to "complete"
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      // Try to interact with view mode buttons
      const listViewButton = canvas.queryByText(/sąrašas/i)
      if (listViewButton) {
        await userEvent.click(listViewButton)
      }
      
      // Wait a bit more for UI updates
      await new Promise(resolve => setTimeout(resolve, 500))
      
    } catch (error) {
      console.warn('Some interactive elements might not be available:', error)
    }
  }
}

// Compact view mode
export const CompactView: Story = {
  decorators: [
    (story) => ({
      components: { story },
      setup() {
        // Mock compact view mode
        mockSearchController.searchState.viewMode = 'compact'
        mockSearchController.searchState.query = 'protokolai'
        mockSearchController.searchState.hits = mockSearchResults
        mockSearchController.searchState.totalHits = 156
        return {}
      },
      template: `
        <div class="min-h-screen bg-gray-50">
          <story />
        </div>
      `
    })
  ],
  render: (args) => ({
    components: { DocumentSearchInterface },
    setup() {
      return { args }
    },
    template: `
      <DocumentSearchInterface v-bind="args" />
    `
  })
}