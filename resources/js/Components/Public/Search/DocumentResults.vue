<template>
  <BaseSearchResults
    :results
    :is-loading
    :has-query
    :search-query
    :total-hits
    :has-more-results
    :is-loading-more
    :has-error
    :has-active-filters
    :skeleton-count="getSkeletonCount()"
    :loading-container-class="viewMode === 'compact' ? 'space-y-2' : 'space-y-4'"
    :results-container-class="viewMode === 'compact' ? 'space-y-2' : 'space-y-4'"
    :transition-name="viewMode === 'compact' ? 'document-compact' : 'document-list'"
    :transition-class="viewMode === 'compact' ? 'space-y-2' : 'space-y-3'"
    no-results-title-key="search.no_documents_found"
    no-results-description-key="search.no_results_criteria"
    empty-state-title-key="search.start_searching"
    empty-state-description-key="search.search_description"
    browse-all-key="search.browse_all_documents"
    @load-more="emit('loadMore')"
    @clear-filters="emit('clearFilters')"
    @retry="emit('retry')"
    @report-error="emit('reportError')"
  >
    <template #skeleton="{ count }">
      <DocumentResultsSkeleton v-for="i in count" :key="i" :view-mode />
    </template>

    <template #item="{ item }">
      <DocumentListItem v-if="viewMode === 'list'" :document="item" />
      <DocumentCompactListItem v-else :document="item" />
    </template>
  </BaseSearchResults>
</template>

<script setup lang="ts">
import BaseSearchResults from './Shared/BaseSearchResults.vue';
import DocumentListItem from './DocumentListItem.vue';
import DocumentCompactListItem from './DocumentCompactListItem.vue';
import DocumentResultsSkeleton from './DocumentResultsSkeleton.vue';

interface Document {
  id: string | number;
  title: string;
  summary?: string;
  content_type?: string;
  language?: string;
  document_date?: string;
  is_in_effect?: boolean | null;
  anonymous_url: string;
  tenant_shortname?: string;
  [key: string]: any;
}

interface Props {
  results: Document[];
  viewMode: 'list' | 'compact';
  isLoading?: boolean;
  hasQuery?: boolean;
  totalHits?: number;
  hasMoreResults?: boolean;
  isLoadingMore?: boolean;
  hasError?: boolean;
  hasActiveFilters?: boolean;
  searchQuery?: string;
}

const props = withDefaults(defineProps<Props>(), {
  results: () => [],
  viewMode: 'list',
  isLoading: false,
  hasQuery: false,
  totalHits: 0,
  hasMoreResults: false,
  isLoadingMore: false,
  hasError: false,
  hasActiveFilters: false,
  searchQuery: '',
});

const emit = defineEmits<{
  loadMore: [];
  clearFilters: [];
  retry: [];
  reportError: [];
}>();

const getSkeletonCount = (): number => {
  if (props.viewMode === 'compact') {
    return 12;
  }
  return 5;
};
</script>
