import type { MeetingSearchFilters, MeetingFacet, ProcessedMeetingSearchResult } from '../Types/MeetingSearchTypes'
import { usePage } from '@inertiajs/vue3'

/**
 * Meeting Search Service - handles Typesense API interactions for meeting search
 * Adapted from DocumentSearchService for meeting-specific requirements
 */
export class MeetingSearchService {
  private abortController: AbortController | null = null
  private searchClient: any
  private collection: string

  constructor(searchClient: any, collectionName?: string) {
    this.searchClient = searchClient
    this.collection = collectionName || 'public_meetings'
  }

  /**
   * Perform search with given filters
   */
  async performSearch(
    filters: MeetingSearchFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number
  ): Promise<ProcessedMeetingSearchResult> {
    // Cancel any ongoing search
    this.cancelCurrentSearch()

    // Create new abort controller for this search
    this.abortController = new AbortController()

    const page = usePage()
    const locale = page.props.app.locale || 'lt'
    const institutionNameField = `institution_name_${locale}`

    // Build search parameters
    const searchParams: any = {
      q: filters.query.trim() || '*',
      query_by: `title,description,${institutionNameField}`,
      query_by_weights: '10,5,3',
      facet_by: 'year,month,tenant_shortname,completion_status,institution_type_title',
      max_facet_values: 50,
      per_page: perPage,
      page: isLoadMore ? currentPage + 1 : 1,
      // Sort: relevance first if query, otherwise newest first
      sort_by: filters.query && filters.query.trim() !== '*'
        ? '_text_match:desc,start_time:desc'
        : 'start_time:desc',
    }

    // Build filter conditions
    const filterConditions = this.buildFilterConditions(filters)
    if (filterConditions.length > 0) {
      searchParams.filter_by = filterConditions.join(' && ')
    }

    try {
      const response = await this.searchClient.search(
        this.collection,
        searchParams,
        this.abortController.signal
      )

      // Process results
      return this.processSearchResponse(response)
    } catch (error) {
      // Re-throw to let composable handle
      throw error
    }
  }

  /**
   * Load initial facets (all available values)
   */
  async loadInitialFacets(): Promise<MeetingFacet[]> {
    // Cancel any ongoing requests
    this.cancelCurrentSearch()
    this.abortController = new AbortController()

    const page = usePage()
    const locale = page.props.app.locale || 'lt'
    const institutionNameField = `institution_name_${locale}`

    const searchParams: any = {
      q: '*',
      query_by: `title,description,${institutionNameField}`,
      facet_by: 'year,month,tenant_shortname,completion_status,institution_type_title',
      max_facet_values: 50,
      per_page: 1, // We only need facets, not results
    }

    try {
      const response = await this.searchClient.search(
        this.collection,
        searchParams,
        this.abortController.signal
      )

      return this.processFacets(response.facet_counts || [])
    } catch (error) {
      console.error('Failed to load initial facets:', error)
      return []
    }
  }

  /**
   * Build filter conditions for Typesense
   */
  private buildFilterConditions(filters: MeetingSearchFilters): string[] {
    const conditions: string[] = []

    // Tenant filter
    if (filters.tenants.length > 0) {
      const tenantConditions = filters.tenants.map(t => `tenant_shortname:="${t}"`)
      conditions.push(`(${tenantConditions.join(' || ')})`)
    }

    // Institution type filter
    if (filters.institutionTypes.length > 0) {
      const typeConditions = filters.institutionTypes.map(t => `institution_type_title:="${t}"`)
      conditions.push(`(${typeConditions.join(' || ')})`)
    }

    // Completion status filter
    if (filters.completionStatus.length > 0) {
      const statusConditions = filters.completionStatus.map(s => `completion_status:="${s}"`)
      conditions.push(`(${statusConditions.join(' || ')})`)
    }

    // Year filter
    if (filters.years.length > 0) {
      const yearConditions = filters.years.map(y => `year:=${y}`)
      conditions.push(`(${yearConditions.join(' || ')})`)
    }

    // Success rate filter
    if (filters.successRateRanges.length > 0) {
      const rateConditions: string[] = []

      if (filters.successRateRanges.includes('high')) {
        rateConditions.push('student_success_rate:>=75')
      }
      if (filters.successRateRanges.includes('medium')) {
        rateConditions.push('student_success_rate:[50..74]')
      }
      if (filters.successRateRanges.includes('low')) {
        rateConditions.push('student_success_rate:<50')
      }

      if (rateConditions.length > 0) {
        conditions.push(`(${rateConditions.join(' || ')})`)
      }
    }

    // Date range filter
    if (filters.dateRange.from || filters.dateRange.to) {
      const from = filters.dateRange.from?.getTime() / 1000 || 0
      const to = filters.dateRange.to?.getTime() / 1000 || Math.floor(Date.now() / 1000)
      conditions.push(`start_time:[${Math.floor(from)}..${Math.floor(to)}]`)
    }

    return conditions
  }

  /**
   * Process search response from Typesense
   */
  private processSearchResponse(response: any): ProcessedMeetingSearchResult {
    const hits = response.hits?.map((hit: any) => hit.document) || []
    const totalHits = response.found || 0
    const facets = this.processFacets(response.facet_counts || [])

    const totalPages = Math.ceil(totalHits / (response.request_params?.per_page || 24))
    const currentPage = response.page || 1

    return {
      hits,
      totalHits,
      facets,
      currentPage,
      totalPages
    }
  }

  /**
   * Process facets from Typesense response
   */
  private processFacets(facetCounts: any[]): MeetingFacet[] {
    const page = usePage()
    const locale = page.props.app.locale || 'lt'

    return facetCounts.map((facetCount: any) => {
      const field = facetCount.field_name

      // Determine label based on field
      let label = field
      if (field === 'tenant_shortname') {
        label = 'Dariniai' // Organizations
      } else if (field === 'completion_status') {
        label = 'Užpildymo būsena' // Completion status
      } else if (field === 'year') {
        label = 'Metai' // Year
      } else if (field === 'month') {
        label = 'Mėnuo' // Month
      }

      // Process values
      const values = (facetCount.counts || []).map((count: any) => ({
        value: String(count.value),
        label: this.formatFacetLabel(field, count.value, locale),
        count: count.count,
        highlighted: count.highlighted,
        isSelected: false
      }))

      return {
        field,
        label,
        values
      }
    })
  }

  /**
   * Format facet label based on field type
   */
  private formatFacetLabel(field: string, value: any, locale: string): string {
    if (field === 'completion_status') {
      const statusLabels: Record<string, Record<string, string>> = {
        'complete': { lt: 'Užpildyti', en: 'Complete' },
        'incomplete': { lt: 'Dalinai užpildyti', en: 'Partially complete' },
        'no_items': { lt: 'Be darbotvarkės', en: 'No agenda' }
      }
      return statusLabels[value]?.[locale] || value
    }

    if (field === 'month') {
      const monthLabels: Record<string, Record<string, string>> = {
        '1': { lt: 'Sausis', en: 'January' },
        '2': { lt: 'Vasaris', en: 'February' },
        '3': { lt: 'Kovas', en: 'March' },
        '4': { lt: 'Balandis', en: 'April' },
        '5': { lt: 'Gegužė', en: 'May' },
        '6': { lt: 'Birželis', en: 'June' },
        '7': { lt: 'Liepa', en: 'July' },
        '8': { lt: 'Rugpjūtis', en: 'August' },
        '9': { lt: 'Rugsėjis', en: 'September' },
        '10': { lt: 'Spalis', en: 'October' },
        '11': { lt: 'Lapkritis', en: 'November' },
        '12': { lt: 'Gruodis', en: 'December' }
      }
      return monthLabels[value]?.[locale] || value
    }

    return String(value)
  }

  /**
   * Cancel current search request
   */
  cancelCurrentSearch(): void {
    if (this.abortController) {
      this.abortController.abort()
      this.abortController = null
    }
  }
}
