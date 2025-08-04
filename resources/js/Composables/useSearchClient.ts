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
}

interface SearchClient {
  search: (collection: string, searchParams: any) => Promise<any>
  apiKey: string
  nodes: TypesenseNode[]
}

export const useSearchClient = () => {
  const searchClient = ref<any>(null)
  const typesenseClient = ref<SearchClient | null>(null)
  const isInitialized = ref(false)
  const initializationError = ref<string | null>(null)

  const initializeSearchClient = () => {
    const page = usePage()
    const typesenseConfig = page.props.typesenseConfig as TypesenseConfig

    if (!typesenseConfig?.apiKey || ['xyz', 'xyza'].includes(typesenseConfig.apiKey)) {
      initializationError.value = 'Typesense not configured - document search unavailable'
      return null
    }

    try {
      // Create InstantSearch adapter (for compatibility)
      const adapter = new TypesenseInstantSearchAdapter({
        server: {
          apiKey: typesenseConfig.apiKey,
          nodes: typesenseConfig.nodes,
          connectionTimeoutSeconds: 10,
        },
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

      searchClient.value = adapter.searchClient

      // Store Typesense configuration for direct API calls
      typesenseClient.value = {
        apiKey: typesenseConfig.apiKey,
        nodes: typesenseConfig.nodes,
        search: async (collection: string, searchParams: any) => {
          const node = typesenseConfig.nodes[0] // Use first node
          if (!node) {
            throw new Error('No Typesense nodes configured')
          }
          const baseUrl = `${node.protocol}://${node.host}:${node.port}`
          const url = new URL(`${baseUrl}/collections/${collection}/documents/search`)
          
          // Add search parameters to URL
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
            }
          })
          
          if (!response.ok) {
            const errorText = await response.text()
            throw new Error(`Typesense API error: ${response.status} - ${errorText}`)
          }
          
          return await response.json()
        }
      }

      isInitialized.value = true
      initializationError.value = null
      return adapter.searchClient
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
