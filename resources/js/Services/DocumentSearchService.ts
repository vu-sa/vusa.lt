import type { DocumentSearchFilters, DocumentFacet, SearchError } from '@/Composables/useDocumentSearch'

interface SearchParams {
  q: string
  query_by: string
  facet_by: string
  max_facet_values: number
  per_page: number
  page: number
  sort_by: string
  filter_by?: string
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

interface SearchClient {
  search: (collection: string, searchParams: SearchParams) => Promise<SearchResponse>
}

export class DocumentSearchService {
  private typesenseClient: SearchClient | null = null
  private abortController: AbortController | null = null

  constructor(typesenseClient: SearchClient | null) {
    this.typesenseClient = typesenseClient
  }

  setClient(client: SearchClient | null) {
    this.typesenseClient = client
  }

  cancelCurrentSearch() {
    if (this.abortController) {
      this.abortController.abort()
    }
  }

  async performSearch(
    filters: DocumentSearchFilters,
    perPage: number,
    isLoadMore = false,
    currentPage = 0
  ): Promise<{
    hits: any[]
    totalHits: number
    facets: DocumentFacet[]
    currentPage: number
    totalPages: number
  }> {
    if (!this.typesenseClient) {
      throw new Error('Typesense client not initialized')
    }

    // Cancel previous request
    this.cancelCurrentSearch()
    this.abortController = new AbortController()

    // Build search parameters
    const searchParams = this.buildSearchParams(filters, perPage, isLoadMore, currentPage)

    try {
      // Execute search with timeout
      const timeoutPromise = new Promise<never>((_, reject) => {
        setTimeout(() => reject(new Error('Search request timed out')), 10000)
      })

      const searchPromise = this.typesenseClient.search('documents', searchParams)
      const response = await Promise.race([searchPromise, timeoutPromise])

      if (this.abortController?.signal.aborted) {
        throw new Error('Search was cancelled')
      }

      // Process results
      const hits = response.hits?.map((hit: any) => hit.document) || []
      const totalHits = response.found || 0
      const totalPages = Math.ceil(totalHits / perPage)
      const newCurrentPage = isLoadMore ? currentPage + 1 : 1

      // Process facets
      const facets = this.processFacets(response.facet_counts || [])

      return {
        hits,
        totalHits,
        facets,
        currentPage: newCurrentPage,
        totalPages
      }
    } catch (error) {
      if (error instanceof Error && error.name === 'AbortError') {
        throw error
      }
      throw new Error(`Search failed: ${error instanceof Error ? error.message : 'Unknown error'}`)
    }
  }

  private buildSearchParams(
    filters: DocumentSearchFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number
  ): SearchParams {
    const searchParams: SearchParams = {
      q: filters.query.trim(),
      query_by: 'title,summary',
      facet_by: [
        'content_type',
        'tenant_shortname',
        'language',
        'document_date'
      ].join(','),
      max_facet_values: 50,
      per_page: perPage,
      page: isLoadMore ? currentPage + 1 : 1,
      sort_by: 'document_date:desc,created_at:desc'
    }

    // Build filter conditions
    const filterConditions = this.buildFilterConditions(filters)
    if (filterConditions.length > 0) {
      searchParams.filter_by = filterConditions.join(' && ')
    }

    return searchParams
  }

  private buildFilterConditions(filters: DocumentSearchFilters): string[] {
    const filterConditions: string[] = ['is_active:=true']

    // Tenant filters
    if (filters.tenants.length > 0) {
      const tenantFilter = filters.tenants.map(t => `"${t}"`).join(',')
      filterConditions.push(`tenant_shortname:[${tenantFilter}]`)
    }

    // Content type filters
    if (filters.contentTypes.length > 0) {
      const typeFilter = filters.contentTypes.map(t => `"${t}"`).join(',')
      filterConditions.push(`content_type:[${typeFilter}]`)
    }

    // Language filters
    if (filters.languages.length > 0) {
      const langFilter = filters.languages.map(l => `"${l}"`).join(',')
      filterConditions.push(`language:[${langFilter}]`)
    }

    // Date range filtering
    const dateFilter = this.buildDateFilter(filters.dateRange)
    if (dateFilter) {
      filterConditions.push(dateFilter)
    }

    return filterConditions
  }

  private buildDateFilter(dateRange: DocumentSearchFilters['dateRange']): string | null {
    if (dateRange.preset === 'custom' && (dateRange.from || dateRange.to)) {
      const fromTimestamp = this.formatDateToTimestamp(dateRange.from) || 0
      const toTimestamp = this.formatDateToTimestamp(dateRange.to) || Math.floor(Date.now() / 1000)
      
      if (fromTimestamp > 0 && toTimestamp > 0) {
        return `document_date:[${fromTimestamp}..${toTimestamp}]`
      }
    } else if (dateRange.preset === 'year-range' && (dateRange.from || dateRange.to)) {
      const fromTimestamp = this.formatDateToTimestamp(dateRange.from) || 0
      const toTimestamp = this.formatDateToTimestamp(dateRange.to) || Math.floor(Date.now() / 1000)
      
      if (fromTimestamp > 0 && toTimestamp > 0) {
        return `document_date:[${fromTimestamp}..${toTimestamp}]`
      }
    } else if (dateRange.preset && dateRange.preset !== 'custom' && dateRange.preset !== 'year-range') {
      const now = Date.now()
      let fromTime: number
      
      switch (dateRange.preset) {
        case 'recent':
          fromTime = now - (3 * 30 * 24 * 60 * 60 * 1000)
          break
        case '3months':
          fromTime = now - (3 * 30 * 24 * 60 * 60 * 1000)
          break
        case '6months':
          fromTime = now - (6 * 30 * 24 * 60 * 60 * 1000)
          break
        case '1year':
          fromTime = now - (365 * 24 * 60 * 60 * 1000)
          break
        default:
          fromTime = now - (3 * 30 * 24 * 60 * 60 * 1000)
      }

      const fromTimestamp = Math.floor(fromTime / 1000)
      const toTimestamp = Math.floor(now / 1000)
      
      return `document_date:[${fromTimestamp}..${toTimestamp}]`
    }

    return null
  }

  private formatDateToTimestamp(date: Date | string | undefined | null): number {
    if (!date) return 0
    
    let dateObj: Date
    if (date instanceof Date) {
      dateObj = date
    } else {
      dateObj = new Date(date)
    }
    
    if (isNaN(dateObj.getTime())) {
      console.warn('Invalid date provided:', date)
      return 0
    }
    
    return Math.floor(dateObj.getTime() / 1000)
  }

  private processFacets(facetCounts: Array<{
    field_name: string
    counts: Array<{ value: string; count: number }>
  }>): DocumentFacet[] {
    return facetCounts.map((facetData) => ({
      field: facetData.field_name,
      label: this.getFacetLabel(facetData.field_name),
      values: (facetData.counts || []).map((countData) => ({
        value: countData.value,
        label: countData.value,
        count: countData.count
      })).sort((a, b) => b.count - a.count)
    }))
  }

  private getFacetLabel(field: string): string {
    const labels: Record<string, string> = {
      'content_type': 'Document Type',
      'tenant_shortname': 'Organization',
      'language': 'Language',
      'document_date': 'Date'
    }
    return labels[field] || field
  }

  async loadInitialFacets(): Promise<DocumentFacet[]> {
    if (!this.typesenseClient) {
      return []
    }

    try {
      const searchRequest: SearchParams = {
        q: '*',
        query_by: 'title,summary',
        facet_by: [
          'content_type',
          'tenant_shortname',
          'language',
          'document_date'
        ].join(','),
        max_facet_values: 50,
        per_page: 1,
        page: 1,
        sort_by: 'document_date:desc,created_at:desc',
        filter_by: 'is_active:=true'
      }

      const response = await this.typesenseClient.search('documents', searchRequest)
      
      return this.processFacets(response.facet_counts || [])
    } catch (error) {
      console.error('Failed to load initial facets:', error)
      return []
    }
  }
}