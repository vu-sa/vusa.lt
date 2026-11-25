import type { TypesenseConfig } from '@/Search/core/types';
import { SearchClientFactory } from '@/Search/core/services/SearchClientFactory';

interface TypesenseClientOptions {
  additionalSearchParameters: Record<string, unknown>;
  collectionSpecificSearchParameters?: Record<string, unknown>;
  connectionTimeoutSeconds?: number;
}

/** Both client aliases use the direct REST client; search services supply request parameters. */
export const createTypesenseClients = (
  typesenseConfig: TypesenseConfig,
  options: TypesenseClientOptions,
  locale = 'lt',
) => {
  if (!typesenseConfig.nodes?.length) {
    throw new Error('No Typesense nodes configured');
  }

  const typesenseClient = SearchClientFactory.createTypesenseClient({
    apiKey: typesenseConfig.apiKey,
    searchProfiles: typesenseConfig.searchProfiles,
    locale,
    collections: typesenseConfig.collections,
    nodes: typesenseConfig.nodes,
    connectionTimeoutSeconds: options.connectionTimeoutSeconds ?? 10,
  });

  return {
    searchClient: typesenseClient,
    typesenseClient,
  };
};
