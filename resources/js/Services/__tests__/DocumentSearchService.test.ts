import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { DocumentSearchService } from '../DocumentSearchService'
import type { DocumentSearchFilters } from '@/Types/DocumentSearchTypes'

// Define interfaces to match actual implementation
interface SearchClient {
  search: (collection: string, searchParams: any) => Promise<SearchResponse>
}

interface SearchResponse {
  hits?: Array<{ document: any }>
  found?: number
  facet_counts?: Array<{
    field_name: string
    counts: Array<{
      value: string
      count: number
    }>
  }>
}

// Mock search client to match actual implementation
const createMockSearchClient = (): SearchClient => ({
  search: vi.fn()
})

// Mock successful search response
const createMockSearchResponse = (overrides = {}): SearchResponse => ({
  hits: [
    {
      document: {
        id: '1',
        title: 'Test Document 1',
        summary: 'Test summary 1',
        content_type: 'Protocol',
        tenant_shortname: 'vu-sa',
        language: 'Lithuanian',
        document_date: 1640995200,
        is_active: true
      }
    },
    {
      document: {
        id: '2', 
        title: 'Test Document 2',
        summary: 'Test summary 2',
        content_type: 'Resolution',
        tenant_shortname: 'vu-mif',
        language: 'English',
        document_date: 1640908800,
        is_active: true
      }
    }
  ],
  found: 2,
  facet_counts: [
    {
      field_name: 'content_type',
      counts: [
        { value: 'Protocol', count: 1 },
        { value: 'Resolution', count: 1 }
      ]
    },
    {
      field_name: 'tenant_shortname',
      counts: [
        { value: 'vu-sa', count: 1 },
        { value: 'vu-mif', count: 1 }
      ]
    },
    {
      field_name: 'language',
      counts: [
        { value: 'Lithuanian', count: 1 },
        { value: 'English', count: 1 }
      ]
    }
  ],
  ...overrides
})

describe('DocumentSearchService', () => {
  let service: DocumentSearchService
  let mockClient: SearchClient
  let baseFilters: DocumentSearchFilters

  beforeEach(() => {
    mockClient = createMockSearchClient()
    service = new DocumentSearchService(mockClient)
    
    baseFilters = {
      query: '',
      tenants: [],
      contentTypes: [],
      languages: [],
      dateRange: {}
    }
    
    vi.clearAllMocks()
  })

  afterEach(() => {
    // Cancel any ongoing searches
    service.cancelCurrentSearch()
  })

  describe('constructor', () => {
    it('initializes with search client', () => {
      expect(service).toBeInstanceOf(DocumentSearchService)
    })

    it('accepts null client and handles gracefully', () => {
      const nullService = new DocumentSearchService(null)
      expect(nullService).toBeInstanceOf(DocumentSearchService)
    })
  })

  describe('performSearch', () => {
    beforeEach(() => {
      vi.mocked(mockClient.search).mockResolvedValue(createMockSearchResponse())
    })

    it('performs basic search with wildcard query', async () => {
      const filters = { ...baseFilters, query: '*' }
      
      const result = await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        q: '*',
        per_page: 24,
        page: 1,
        query_by: 'title,summary,content_type,document_year,document_date_formatted',
        query_by_weights: '10,3,2,6,4',
        facet_by: 'content_type,tenant_shortname,language,document_date,is_in_effect',
        max_facet_values: 50,
        sort_by: 'document_date:desc,created_at:desc',
        filter_by: 'is_active:=true',
        prefix: false,
        infix: 'fallback',
        prioritize_exact_match: true,
        prioritize_token_position: true,
        typo_tokens_threshold: 2,
        min_len_1typo: 4,
        min_len_2typo: 7,
        drop_tokens_threshold: 10
      }))
      
      expect(result.hits).toHaveLength(2)
      expect(result.totalHits).toBe(2)
      expect(result.facets).toHaveLength(3)
      expect(result.currentPage).toBe(1)
      expect(result.totalPages).toBe(1)
    })

    it('performs text search with query', async () => {
      const filters = { ...baseFilters, query: 'test search' }
      
      await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        q: 'test search',
        query_by: 'title,summary,content_type,document_year,document_date_formatted',
        query_by_weights: '10,3,2,6,4',
        sort_by: '_text_match:desc,document_date:desc',
        filter_by: 'is_active:=true'
      }))
    })

    it('applies tenant filters correctly', async () => {
      const filters = { ...baseFilters, query: '*', tenants: ['vu-sa', 'vu-mif'] }
      
      await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        filter_by: 'is_active:=true && (tenant_shortname:="vu-sa" || tenant_shortname:="vu-mif")'
      }))
    })

    it('applies content type filters correctly', async () => {
      const filters = { ...baseFilters, query: '*', contentTypes: ['Protocol', 'Resolution'] }
      
      await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        filter_by: 'is_active:=true && (content_type:="Protocol" || content_type:="Resolution")'
      }))
    })

    it('applies language filters correctly', async () => {
      const filters = { ...baseFilters, query: '*', languages: ['Lithuanian', 'English'] }
      
      await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        filter_by: 'is_active:=true && (language:="Lithuanian" || language:="English")'
      }))
    })

    it('applies date range filters with preset', async () => {
      const filters = { 
        ...baseFilters, 
        query: '*', 
        dateRange: { preset: '3months' as const }
      }
      
      await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        filter_by: expect.stringMatching(/document_date:\[\d+\.\.\d+\]/)
      }))
    })

    it('applies date range filters with custom dates', async () => {
      const fromDate = new Date('2024-01-01')
      const toDate = new Date('2024-12-31')
      const filters = { 
        ...baseFilters, 
        query: '*', 
        dateRange: { preset: 'custom' as const, from: fromDate, to: toDate }
      }
      
      await service.performSearch(filters, 24, false, 0)
      
      const expectedFrom = Math.floor(fromDate.getTime() / 1000)
      const expectedTo = Math.floor(toDate.getTime() / 1000)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        filter_by: `is_active:=true && document_date:[${expectedFrom}..${expectedTo}]`
      }))
    })

    it('combines multiple filters correctly', async () => {
      const filters = { 
        ...baseFilters, 
        query: 'test',
        tenants: ['vu-sa'],
        contentTypes: ['Protocol'],
        languages: ['Lithuanian']
      }
      
      await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        filter_by: 'is_active:=true && (tenant_shortname:="vu-sa") && (content_type:="Protocol") && (language:="Lithuanian")'
      }))
    })

    it('handles load more correctly', async () => {
      const filters = { ...baseFilters, query: '*' }
      
      await service.performSearch(filters, 24, true, 1) // isLoadMore = true, currentPage = 1
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        page: 2 // Should increment page for load more
      }))
    })

    it('calculates pagination correctly', async () => {
      const mockResponse = createMockSearchResponse({ found: 50 })
      vi.mocked(mockClient.search).mockResolvedValue(mockResponse)
      
      const result = await service.performSearch(baseFilters, 24, false, 0)
      
      expect(result.totalHits).toBe(50)
      expect(result.totalPages).toBe(3) // Math.ceil(50 / 24)
      expect(result.currentPage).toBe(1)
    })

    it('processes facets correctly', async () => {
      const result = await service.performSearch(baseFilters, 24, false, 0)
      
      expect(result.facets).toHaveLength(3)
      
      const contentTypeFacet = result.facets.find(f => f.field === 'content_type')
      expect(contentTypeFacet).toBeDefined()
      expect(contentTypeFacet?.values).toHaveLength(2)
      expect(contentTypeFacet?.values[0]).toMatchObject({
        value: 'Protocol',
        label: 'Protocol',
        count: 1
      })
    })

    it('handles empty search results', async () => {
      const mockResponse = createMockSearchResponse({ hits: [], found: 0, facet_counts: [] })
      vi.mocked(mockClient.search).mockResolvedValue(mockResponse)
      
      const result = await service.performSearch(baseFilters, 24, false, 0)
      
      expect(result.hits).toHaveLength(0)
      expect(result.totalHits).toBe(0)
      expect(result.facets).toHaveLength(0)
    })

    it('handles special characters in filter values', async () => {
      const filters = { 
        ...baseFilters, 
        query: '*',
        tenants: ['vu-sa [special]', 'test & co']
      }
      
      await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        filter_by: 'is_active:=true && (tenant_shortname:="vu-sa [special]" || tenant_shortname:="test & co")'
      }))
    })
  })

  describe('loadInitialFacets', () => {
    beforeEach(() => {
      vi.mocked(mockClient.search).mockResolvedValue(createMockSearchResponse())
    })

    it('loads facets with wildcard search', async () => {
      const facets = await service.loadInitialFacets()
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        q: '*',
        per_page: 1, // Minimal results needed
        page: 1,
        query_by: 'title,summary,content_type,document_year,document_date_formatted',
        query_by_weights: '10,3,2,6,4',
        facet_by: 'content_type,tenant_shortname,language,document_date,is_in_effect',
        max_facet_values: 50,
        sort_by: 'document_date:desc,created_at:desc',
        filter_by: 'is_active:=true'
      }))
      
      expect(facets).toHaveLength(3)
    })

    it('handles facet loading errors gracefully', async () => {
      vi.mocked(mockClient.search).mockRejectedValue(new Error('Facet loading failed'))
      
      const facets = await service.loadInitialFacets()
      
      expect(facets).toEqual([]) // Should return empty array, not throw
    })
  })

  describe('error handling', () => {
    it('handles network errors', async () => {
      const networkError = new Error('fetch failed')
      vi.mocked(mockClient.search).mockRejectedValue(networkError)
      
      await expect(service.performSearch(baseFilters, 24, false, 0))
        .rejects.toThrow('Search failed: fetch failed')
    })

    it('handles Typesense API errors', async () => {
      const apiError = new Error('Typesense API error: 404 - Collection not found')
      vi.mocked(mockClient.search).mockRejectedValue(apiError)
      
      await expect(service.performSearch(baseFilters, 24, false, 0))
        .rejects.toThrow('Search failed: Typesense API error: 404 - Collection not found')
    })
    
    it('throws error when client is null', async () => {
      const nullService = new DocumentSearchService(null)
      
      await expect(nullService.performSearch(baseFilters, 24, false, 0))
        .rejects.toThrow('Typesense client not initialized')
    })

    it('handles malformed responses', async () => {
      vi.mocked(mockClient.search).mockResolvedValue({} as any)
      
      const result = await service.performSearch(baseFilters, 24, false, 0)
      
      expect(result.hits).toEqual([])
      expect(result.totalHits).toBe(0)
      expect(result.facets).toEqual([])
    })
  })

  describe('request cancellation', () => {
    it('supports AbortSignal for cancelling requests', async () => {
      const abortController = new AbortController()
      
      // Mock a slow response
      vi.mocked(mockClient.search).mockImplementation(async (collection, params, signal) => {
        return new Promise((resolve, reject) => {
          const timeout = setTimeout(() => resolve(createMockSearchResponse()), 1000)
          signal?.addEventListener('abort', () => {
            clearTimeout(timeout)
            reject(new Error('AbortError'))
          })
        })
      })
      
      const searchPromise = service.performSearch(baseFilters, 24, false, 0)
      
      // Cancel the request
      service.cancelCurrentSearch()
      
      await expect(searchPromise).rejects.toThrow()
    })

    it('cancels ongoing search when cancelCurrentSearch is called', () => {
      const spy = vi.spyOn(service, 'cancelCurrentSearch')
      
      service.cancelCurrentSearch()
      
      expect(spy).toHaveBeenCalled()
    })
  })

  describe('search parameter validation', () => {
    it('handles page numbers correctly', async () => {
      vi.mocked(mockClient.search).mockResolvedValue(createMockSearchResponse())
      
      // Test normal page handling  
      await service.performSearch(baseFilters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        page: 1 // First page for new search
      }))
    })

    it('passes through per_page values as provided', async () => {
      vi.mocked(mockClient.search).mockResolvedValue(createMockSearchResponse())
      
      // The service passes through per_page as provided
      await service.performSearch(baseFilters, 0, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        per_page: 0 // Passes through the value
      }))
    })

    it('passes through large per_page values', async () => {
      vi.mocked(mockClient.search).mockResolvedValue(createMockSearchResponse())
      
      await service.performSearch(baseFilters, 1000, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        per_page: 1000 // Passes through large values
      }))
    })
  })

  describe('search optimization', () => {
    it('uses consistent search settings for all queries', async () => {
      vi.mocked(mockClient.search).mockResolvedValue(createMockSearchResponse())
      const filters = { ...baseFilters, query: 'specific search terms' }
      
      await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        query_by: 'title,summary,content_type,document_year,document_date_formatted',
        query_by_weights: '10,3,2,6,4',
        sort_by: '_text_match:desc,document_date:desc',
        max_facet_values: 50,
        prefix: false,
        infix: 'fallback',
        prioritize_exact_match: true,
        prioritize_token_position: true,
        typo_tokens_threshold: 2,
        min_len_1typo: 4,
        min_len_2typo: 7,
        drop_tokens_threshold: 10
      }))
    })

    it('uses same settings for wildcard queries', async () => {
      vi.mocked(mockClient.search).mockResolvedValue(createMockSearchResponse())
      const filters = { ...baseFilters, query: '*' }
      
      await service.performSearch(filters, 24, false, 0)
      
      expect(mockClient.search).toHaveBeenCalledWith('documents', expect.objectContaining({
        q: '*',
        query_by: 'title,summary,content_type,document_year,document_date_formatted',
        query_by_weights: '10,3,2,6,4',
        sort_by: 'document_date:desc,created_at:desc'
      }))
    })
  })

  describe('facet processing', () => {
    it('processes tenant facets with proper labels', async () => {
      const mockResponse = createMockSearchResponse({
        facet_counts: [{
          field_name: 'tenant_shortname',
          counts: [
            { value: 'vu-sa', count: 5 },
            { value: 'vu-mif', count: 3 }
          ]
        }]
      })
      vi.mocked(mockClient.search).mockResolvedValue(mockResponse)
      
      const result = await service.performSearch(baseFilters, 24, false, 0)
      
      const tenantFacet = result.facets.find(f => f.field === 'tenant_shortname')
      expect(tenantFacet?.label).toBe('Organization')
      expect(tenantFacet?.values).toHaveLength(2)
    })

    it('handles missing facet data gracefully', async () => {
      const mockResponse = createMockSearchResponse({ facet_counts: undefined })
      vi.mocked(mockClient.search).mockResolvedValue(mockResponse)
      
      const result = await service.performSearch(baseFilters, 24, false, 0)
      
      expect(result.facets).toEqual([])
    })

    it('sorts facet values by count descending', async () => {
      const mockResponse = createMockSearchResponse({
        facet_counts: [{
          field_name: 'content_type',
          counts: [
            { value: 'Protocol', count: 2 },
            { value: 'Resolution', count: 5 },
            { value: 'Decision', count: 3 }
          ]
        }]
      })
      vi.mocked(mockClient.search).mockResolvedValue(mockResponse)
      
      const result = await service.performSearch(baseFilters, 24, false, 0)
      
      const contentTypeFacet = result.facets.find(f => f.field === 'content_type')
      expect(contentTypeFacet?.values[0].count).toBe(5) // Resolution should be first
      expect(contentTypeFacet?.values[1].count).toBe(3) // Decision should be second
      expect(contentTypeFacet?.values[2].count).toBe(2) // Protocol should be last
    })
  })
})
