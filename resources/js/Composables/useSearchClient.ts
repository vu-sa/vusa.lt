import { ref, type Ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { SearchClientFactory, type TypesenseClient } from '@/Shared/Search/services/SearchClientFactory';

interface TypesenseNode {
  protocol: string;
  host: string;
  port: number;
}

interface TypesenseConfig {
  apiKey: string;
  nodes: TypesenseNode[];
  collections?: Record<string, string>;
}

interface TypesenseClientOptions {
  additionalSearchParameters: Record<string, any>;
  collectionSpecificSearchParameters?: Record<string, any>;
  connectionTimeoutSeconds?: number;
}

/**
 * Create a direct Typesense client for the dedicated search pages.
 *
 * Historically this also returned a `typesense-instantsearch-adapter` `searchClient`,
 * but the public search no longer uses InstantSearch. We keep the `{ searchClient,
 * typesenseClient }` shape for backward compatibility — both now point to the same
 * direct REST client (`SearchClientFactory.createTypesenseClient`). The
 * `additionalSearchParameters`/`collectionSpecificSearchParameters` options are
 * accepted for call-site compatibility; per-request parameters are supplied by the
 * search services themselves.
 */
export const createTypesenseClients = (
  typesenseConfig: TypesenseConfig,
  options: TypesenseClientOptions,
) => {
  if (!typesenseConfig.nodes?.length) {
    throw new Error('No Typesense nodes configured');
  }

  const typesenseClient = SearchClientFactory.createTypesenseClient({
    apiKey: typesenseConfig.apiKey,
    nodes: typesenseConfig.nodes,
    connectionTimeoutSeconds: options.connectionTimeoutSeconds ?? 10,
  });

  return {
    searchClient: typesenseClient,
    typesenseClient,
  };
};

export const useSearchClient = () => {
  const searchClient = ref<TypesenseClient | null>(null);
  const typesenseClient = ref<TypesenseClient | null>(null);
  const isInitialized = ref(false);
  const initializationError = ref<string | null>(null);

  const initializeSearchClient = () => {
    const page = usePage();
    const typesenseConfig = page.props.typesenseConfig as TypesenseConfig;

    if (!typesenseConfig?.apiKey) {
      initializationError.value = 'Typesense not configured - document search unavailable';
      return null;
    }

    try {
      const clients = createTypesenseClients(typesenseConfig, {
        additionalSearchParameters: {},
      });

      searchClient.value = clients.searchClient;
      typesenseClient.value = clients.typesenseClient;

      isInitialized.value = true;
      initializationError.value = null;
      return clients.searchClient;
    }
    catch (error) {
      initializationError.value = `Failed to initialize Typesense client: ${error instanceof Error ? error.message : 'Unknown error'}`;
      return null;
    }
  };

  return {
    searchClient: searchClient as Ref<TypesenseClient | null>,
    typesenseClient: typesenseClient as Ref<TypesenseClient | null>,
    isInitialized,
    initializationError,
    initializeSearchClient,
  };
};

export type SearchClientController = ReturnType<typeof useSearchClient>;
