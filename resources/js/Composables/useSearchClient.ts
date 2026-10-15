import type { SearchProfileConfig } from '@/Shared/Search/profiles';
import { SearchClientFactory } from '@/Shared/Search/services/SearchClientFactory';

interface TypesenseNode {
  protocol: string;
  host: string;
  port: number;
}

interface TypesenseConfig extends SearchProfileConfig {
  apiKey: string;
  nodes: TypesenseNode[];
  collections?: Record<string, string>;
}

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
