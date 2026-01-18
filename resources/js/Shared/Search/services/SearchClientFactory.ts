/**
 * SearchClientFactory - Unified search client factory
 *
 * Creates Typesense search clients for both public and admin contexts.
 * Uses factory pattern to abstract the differences in authentication:
 * - Public: Uses shared public API key from page props
 * - Admin: Uses scoped API keys fetched from backend per collection
 *
 * Used by both admin and public search implementations.
 */

import type { TypesenseConfig, TypesenseNode, SearchClientType, SearchClients } from '../types'

/**
 * Typesense client interface for direct API access
 */
export interface TypesenseClient {
  apiKey: string
  nodes: TypesenseNode[]
  search: (
    collection: string,
    searchParams: Record<string, unknown>,
    abortSignal?: AbortSignal
  ) => Promise<TypesenseSearchResponse>
}

/**
 * Typesense search response
 */
export interface TypesenseSearchResponse {
  hits?: Array<{ document: Record<string, unknown>; highlights?: unknown[] }>
  found?: number
  page?: number
  search_time_ms?: number
  facet_counts?: Array<{
    field_name: string
    counts: Array<{ value: string; count: number }>
  }>
}

/**
 * Options for creating a search client
 */
export interface ClientFactoryOptions {
  /** API key (for public) or scoped key (for admin) */
  apiKey: string
  /** Typesense nodes */
  nodes: TypesenseNode[]
  /** Connection timeout in seconds */
  connectionTimeoutSeconds?: number
  /** Additional search parameters */
  additionalSearchParameters?: Record<string, unknown>
  /** Collection-specific search parameters */
  collectionSpecificSearchParameters?: Record<string, Record<string, unknown>>
}

/**
 * SearchClientFactory - Creates search clients
 */
export class SearchClientFactory {
  /**
   * Create a Typesense direct client (no InstantSearch adapter)
   *
   * This is the preferred client for custom search implementations.
   */
  static createTypesenseClient(options: ClientFactoryOptions): TypesenseClient {
    const { apiKey, nodes, connectionTimeoutSeconds = 10 } = options

    if (!nodes?.length) {
      throw new Error('No Typesense nodes configured')
    }

    return {
      apiKey,
      nodes,
      search: async (
        collection: string,
        searchParams: Record<string, unknown>,
        abortSignal?: AbortSignal
      ): Promise<TypesenseSearchResponse> => {
        const node = nodes[0]
        const baseUrl = `${node.protocol}://${node.host}:${node.port}`
        const url = new URL(`${baseUrl}/collections/${collection}/documents/search`)

        // Build query parameters
        for (const [key, value] of Object.entries(searchParams)) {
          if (value !== undefined && value !== null && value !== '') {
            url.searchParams.append(key, String(value))
          }
        }

        const controller = new AbortController()
        const timeoutId = setTimeout(() => controller.abort(), connectionTimeoutSeconds * 1000)

        try {
          const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
              'X-TYPESENSE-API-KEY': apiKey,
              'Content-Type': 'application/json'
            },
            signal: abortSignal || controller.signal
          })

          if (!response.ok) {
            const errorText = await response.text()
            throw new Error(`Typesense API error: ${response.status} - ${errorText}`)
          }

          return await response.json()
        } finally {
          clearTimeout(timeoutId)
        }
      }
    }
  }

  /**
   * Create a public search client from page props config
   */
  static createPublicClient(config: TypesenseConfig): TypesenseClient {
    return this.createTypesenseClient({
      apiKey: config.apiKey,
      nodes: config.nodes
    })
  }

  /**
   * Create an admin search client with a scoped API key
   *
   * @param apiKey - Scoped API key for the collection
   * @param nodes - Typesense nodes
   */
  static createAdminClient(apiKey: string, nodes: TypesenseNode[]): TypesenseClient {
    return this.createTypesenseClient({
      apiKey,
      nodes
    })
  }

  /**
   * Create both direct and InstantSearch adapter clients
   *
   * Used when InstantSearch compatibility is needed.
   */
  static async createWithAdapter(options: ClientFactoryOptions): Promise<SearchClients> {
    const typesenseClient = this.createTypesenseClient(options)

    // Dynamically import the InstantSearch adapter only when needed
    const { default: TypesenseInstantSearchAdapter } = await import(
      'typesense-instantsearch-adapter'
    )

    const adapter = new TypesenseInstantSearchAdapter({
      server: {
        apiKey: options.apiKey,
        nodes: options.nodes,
        connectionTimeoutSeconds: options.connectionTimeoutSeconds ?? 10
      },
      additionalSearchParameters: options.additionalSearchParameters || {},
      collectionSpecificSearchParameters: options.collectionSpecificSearchParameters
    })

    return {
      typesenseClient,
      searchClient: adapter.searchClient
    }
  }

  /**
   * Validate a Typesense configuration
   */
  static isValidConfig(config: unknown): config is TypesenseConfig {
    if (!config || typeof config !== 'object') return false
    const c = config as Record<string, unknown>
    return typeof c.apiKey === 'string' && c.apiKey.length > 0 && Array.isArray(c.nodes)
  }
}

/**
 * Helper to build search parameters for common queries
 */
export class SearchParamsBuilder {
  private params: Record<string, string | number> = {}

  constructor(query = '*') {
    this.params.q = query
  }

  queryBy(fields: string | string[]): this {
    this.params.query_by = Array.isArray(fields) ? fields.join(',') : fields
    return this
  }

  facetBy(fields: string | string[]): this {
    this.params.facet_by = Array.isArray(fields) ? fields.join(',') : fields
    return this
  }

  filterBy(filter: string): this {
    if (filter) {
      this.params.filter_by = filter
    }
    return this
  }

  sortBy(field: string, direction: 'asc' | 'desc' = 'desc'): this {
    this.params.sort_by = `${field}:${direction}`
    return this
  }

  perPage(count: number): this {
    this.params.per_page = count
    return this
  }

  page(page: number): this {
    this.params.page = page
    return this
  }

  maxFacetValues(count: number): this {
    this.params.max_facet_values = count
    return this
  }

  numTypos(count: number): this {
    this.params.num_typos = count
    return this
  }

  typoTokensThreshold(count: number): this {
    this.params.typo_tokens_threshold = count
    return this
  }

  dropTokensThreshold(count: number): this {
    this.params.drop_tokens_threshold = count
    return this
  }

  build(): Record<string, string | number> {
    return { ...this.params }
  }
}

/**
 * Helper to build filter strings for Typesense
 */
export class FilterBuilder {
  private parts: string[] = []

  /**
   * Add an exact match filter
   */
  equals(field: string, value: string | number | boolean): this {
    if (value !== undefined && value !== null) {
      if (typeof value === 'string') {
        this.parts.push(`${field}:=${this.escapeValue(value)}`)
      } else {
        this.parts.push(`${field}:${value}`)
      }
    }
    return this
  }

  /**
   * Add an array filter (any of values)
   */
  anyOf(field: string, values: (string | number)[]): this {
    if (values.length === 0) return this

    if (typeof values[0] === 'string') {
      const escaped = values.map(v => this.escapeValue(v as string))
      this.parts.push(`${field}:[${escaped.join(',')}]`)
    } else {
      this.parts.push(`${field}:[${values.join(',')}]`)
    }
    return this
  }

  /**
   * Add a range filter
   */
  range(field: string, min?: number, max?: number): this {
    if (min !== undefined && max !== undefined) {
      this.parts.push(`${field}:[${min}..${max}]`)
    } else if (min !== undefined) {
      this.parts.push(`${field}:>=${min}`)
    } else if (max !== undefined) {
      this.parts.push(`${field}:<=${max}`)
    }
    return this
  }

  /**
   * Add a timestamp range filter
   */
  timestampRange(field: string, from?: Date, to?: Date): this {
    if (from && to) {
      const fromTs = Math.floor(from.getTime() / 1000)
      const toTs = Math.floor(to.getTime() / 1000)
      this.parts.push(`${field}:[${fromTs}..${toTs}]`)
    } else if (from) {
      const fromTs = Math.floor(from.getTime() / 1000)
      this.parts.push(`${field}:>=${fromTs}`)
    } else if (to) {
      const toTs = Math.floor(to.getTime() / 1000)
      this.parts.push(`${field}:<=${toTs}`)
    }
    return this
  }

  /**
   * Escape special characters in filter values
   */
  private escapeValue(value: string): string {
    if (value.includes(',') || value.includes(':') || value.includes('`')) {
      return `\`${value.replace(/`/g, '\\`')}\``
    }
    return value
  }

  /**
   * Build the filter string
   */
  build(): string {
    return this.parts.join(' && ')
  }

  /**
   * Check if any filters have been added
   */
  isEmpty(): boolean {
    return this.parts.length === 0
  }
}
