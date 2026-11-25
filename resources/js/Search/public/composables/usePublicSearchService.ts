import { computed, nextTick, shallowRef } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { BaseSearchService } from '@/Search/core/composables/useBaseSearch';
import { SearchClientFactory, type TypesenseClient } from '@/Search/core/services/SearchClientFactory';
import type { BaseFacet, BaseSearchFilters } from '@/Search/core/types';

export function usePublicSearchService<TFilters extends BaseSearchFilters, TFacet extends BaseFacet, TResult, TDocument = Record<string, unknown>>(
  baseSearch: {
    setSearchService: (service: BaseSearchService<TFilters, TFacet, TResult>) => void;
    loadInitialFacets: () => Promise<void>;
  },
  collection: string,
  createService: (client: TypesenseClient<TDocument>, collection: string, getLocale: () => string) => BaseSearchService<TFilters, TFacet, TResult>,
) {
  const page = usePage();
  const client = shallowRef<TypesenseClient<TDocument> | null>(null);
  const getLocale = () => page.props.app?.locale || 'lt';

  async function initializeSearchClient() {
    try {
      // Service-backed listings have historically used the default profile locale.
      const search = SearchClientFactory.resolvePublicSearch<TDocument>(page.props.typesenseConfig, collection);
      if (!search) {
        console.warn(`Typesense not configured - ${collection} search unavailable`);
        return null;
      }

      baseSearch.setSearchService(createService(search.client, search.collection, getLocale));
      client.value = search.client;
      await nextTick();
      await baseSearch.loadInitialFacets();
      return search.client;
    }
    catch (error) {
      console.error('Failed to initialize Typesense clients:', error);
      return null;
    }
  }

  return { searchClient: computed(() => client.value), initializeSearchClient };
}
