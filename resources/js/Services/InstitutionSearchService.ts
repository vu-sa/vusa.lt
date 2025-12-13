import type { InstitutionSearchFilters, InstitutionFacet } from '@/Types/InstitutionSearchTypes'

interface SearchParams {
  q: string
  query_by: string
  query_by_weights?: string
  facet_by: string
  max_facet_values: number
  per_page: number
  page: number
  sort_by: string
  filter_by?: string
  prefix?: boolean
  infix?: string
  prioritize_exact_match?: boolean
  prioritize_token_position?: boolean
  typo_tokens_threshold?: number
  min_len_1typo?: number
  min_len_2typo?: number
  drop_tokens_threshold?: number
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

export class InstitutionSearchService {
  private typesenseClient: SearchClient | null = null
  private abortController: AbortController | null = null
  private collectionName: string

  constructor(typesenseClient: SearchClient | null, collectionName?: string) {
    this.typesenseClient = typesenseClient
    this.collectionName = collectionName || 'public_institutions'
  }

  setClient(client: SearchClient | null) {
    this.typesenseClient = client
  }

  setCollectionName(name: string) {
    this.collectionName = name
  }

  cancelCurrentSearch() {
    if (this.abortController) {
      this.abortController.abort()
    }
  }

  async performSearch(
    filters: InstitutionSearchFilters,
    perPage: number,
    isLoadMore = false,
    currentPage = 0,
    locale = 'lt'
  ): Promise<{
    hits: any[]
    totalHits: number
    facets: InstitutionFacet[]
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
    const searchParams = this.buildSearchParams(filters, perPage, isLoadMore, currentPage, locale)

    try {
      // Execute search with timeout
      const timeoutPromise = new Promise<never>((_, reject) => {
        setTimeout(() => reject(new Error('Search request timed out')), 10000)
      })

      const searchPromise = this.typesenseClient.search(this.collectionName, searchParams)
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
    filters: InstitutionSearchFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number,
    locale: string
  ): SearchParams {
    const query = filters.query.trim()
    
    // Search in locale-appropriate fields with weights (no descriptions - they can be very long)
    const queryByFields = locale === 'en' 
      ? 'name_en,name_lt,short_name_en,short_name_lt,alias'
      : 'name_lt,name_en,short_name_lt,short_name_en,alias'
    
    const searchParams: SearchParams = {
      q: query || '*',
      query_by: queryByFields,
      query_by_weights: '10,8,6,4,3',
      facet_by: [
        'tenant_shortname',
        'type_slugs',
        'has_contacts'
      ].join(','),
      max_facet_values: 50,
      per_page: perPage,
      page: isLoadMore ? currentPage + 1 : 1,
      // Smart sorting: relevance for searches, logos first then alphabetical for browsing
      sort_by: (query && query !== '*') 
        ? '_text_match:desc,has_logo:desc,name_lt:asc' 
        : (locale === 'en' ? 'has_logo:desc,name_en:asc,name_lt:asc' : 'has_logo:desc,name_lt:asc,name_en:asc'),
      prefix: true,
      infix: 'fallback',
      prioritize_exact_match: true,
      prioritize_token_position: true,
      typo_tokens_threshold: 2,
      min_len_1typo: 3,
      min_len_2typo: 6,
      drop_tokens_threshold: 5
    }

    // Build filter conditions
    const filterConditions = this.buildFilterConditions(filters)
    if (filterConditions.length > 0) {
      searchParams.filter_by = filterConditions.join(' && ')
    }

    return searchParams
  }

  private buildFilterConditions(filters: InstitutionSearchFilters): string[] {
    const filterConditions: string[] = []

    // Tenant filters - use exact match for each tenant
    if (filters.tenants.length > 0) {
      const tenantConditions = filters.tenants.map(t => `tenant_shortname:="${t}"`)
      filterConditions.push(`(${tenantConditions.join(' || ')})`)
    }

    // Type filters - use exact match for each type slug
    if (filters.types.length > 0) {
      const typeConditions = filters.types.map(t => `type_slugs:="${t}"`)
      filterConditions.push(`(${typeConditions.join(' || ')})`)
    }

    // Has contacts filter
    if (filters.hasContacts !== null) {
      filterConditions.push(`has_contacts:=${filters.hasContacts}`)
    }

    return filterConditions
  }

  private processFacets(facetCounts: Array<{
    field_name: string
    counts: Array<{ value: string; count: number }>
  }>): InstitutionFacet[] {
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
      'tenant_shortname': 'Organization',
      'type_slugs': 'Type',
      'has_contacts': 'Has Contacts'
    }
    return labels[field] || field
  }

  async loadInitialFacets(): Promise<InstitutionFacet[]> {
    if (!this.typesenseClient) {
      return []
    }

    try {
      const searchRequest: SearchParams = {
        q: '*',
        query_by: 'name_lt,name_en,short_name_lt,short_name_en,alias',
        facet_by: [
          'tenant_shortname',
          'type_slugs',
          'has_contacts'
        ].join(','),
        max_facet_values: 50,
        per_page: 1,
        page: 1,
        sort_by: 'has_logo:desc,name_lt:asc'
      }

      const response = await this.typesenseClient.search(this.collectionName, searchRequest)
      
      return this.processFacets(response.facet_counts || [])
    } catch (error) {
      console.error('Failed to load initial facets:', error)
      return []
    }
  }
}
