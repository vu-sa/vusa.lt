import { ref, type Ref } from 'vue'
import TypesenseInstantSearchAdapter from 'typesense-instantsearch-adapter'
import { usePage } from '@inertiajs/vue3'

interface TypesenseNode {
  protocol: string
  host: string
  port: number
}

interface TypesenseConfig {
  apiKey: string
  nodes: TypesenseNode[]
  collections?: Record<string, string>
}

interface SearchClient {
  search: (collection: string, searchParams: Record<string, any>, abortSignal?: AbortSignal) => Promise<any>
  apiKey: string
  nodes: TypesenseNode[]
}

interface TypesenseClientOptions {
  additionalSearchParameters: Record<string, any>
  collectionSpecificSearchParameters?: Record<string, any>
  connectionTimeoutSeconds?: number
}

export const createTypesenseClients = (
  typesenseConfig: TypesenseConfig,
  options: TypesenseClientOptions
) => {
  if (!typesenseConfig.nodes?.length) {
    throw new Error('No Typesense nodes configured')
  }

  const adapter = new TypesenseInstantSearchAdapter({
    server: {
      apiKey: typesenseConfig.apiKey,
      nodes: typesenseConfig.nodes,
      connectionTimeoutSeconds: options.connectionTimeoutSeconds ?? 10,
    },
    additionalSearchParameters: options.additionalSearchParameters,
    collectionSpecificSearchParameters: options.collectionSpecificSearchParameters
  })

  const typesenseClient: SearchClient = {
    apiKey: typesenseConfig.apiKey,
    nodes: typesenseConfig.nodes,
    search: async (collection: string, searchParams: Record<string, any>, abortSignal?: AbortSignal) => {
      const node = typesenseConfig.nodes[0]
      const baseUrl = `${node.protocol}://${node.host}:${node.port}`
      const url = new URL(`${baseUrl}/collections/${collection}/documents/search`)

      Object.entries(searchParams).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          url.searchParams.append(key, String(value))
        }
      })

      const response = await fetch(url.toString(), {
        method: 'GET',
        headers: {
          'X-TYPESENSE-API-KEY': typesenseConfig.apiKey,
          'Content-Type': 'application/json',
        },
        signal: abortSignal
      })

      if (!response.ok) {
        const errorText = await response.text()
        throw new Error(`Typesense API error: ${response.status} - ${errorText}`)
      }

      return await response.json()
    }
  }

  return {
    searchClient: adapter.searchClient,
    typesenseClient
  }
}

export const useSearchClient = () => {
  const searchClient = ref<any>(null)
  const typesenseClient = ref<SearchClient | null>(null)
  const isInitialized = ref(false)
  const initializationError = ref<string | null>(null)

  const initializeSearchClient = () => {
    const page = usePage()
    const typesenseConfig = page.props.typesenseConfig as TypesenseConfig

    if (!typesenseConfig?.apiKey) {
      initializationError.value = 'Typesense not configured - document search unavailable'
      return null
    }

    try {
      const clients = createTypesenseClients(typesenseConfig, {
        additionalSearchParameters: {
          query_by: 'title,summary',
          num_typos: 2,
          typo_tokens_threshold: 1,
          drop_tokens_threshold: 1,
          max_hits: 1000,
          per_page: 20,
          facet_by: [
            'content_type',
            'tenant_shortname',
            'language',
            'document_date'
          ].join(','),
          max_facet_values: 50,
          sort_by: 'document_date:desc,created_at:desc'
        },
        collectionSpecificSearchParameters: {
          documents: {
            query_by: 'title,summary',
            facet_by: [
              'content_type',
              'tenant_shortname',
              'language',
              'document_date'
            ].join(','),
            per_page: 24,
          }
        }
      })

      searchClient.value = clients.searchClient
      typesenseClient.value = clients.typesenseClient

      isInitialized.value = true
      initializationError.value = null
      return clients.searchClient
    } catch (error) {
      initializationError.value = `Failed to initialize Typesense client: ${error instanceof Error ? error.message : 'Unknown error'}`
      return null
    }
  }

  return {
    searchClient: searchClient as Ref<any>,
    typesenseClient: typesenseClient as Ref<SearchClient | null>,
    isInitialized,
    initializationError,
    initializeSearchClient
  }
}

export type SearchClientController = ReturnType<typeof useSearchClient>
