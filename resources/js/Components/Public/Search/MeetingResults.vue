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
    :skeleton-count="6"
    loading-container-class="space-y-2"
    results-container-class="space-y-2"
    transition-name="meeting-compact"
    transition-class="space-y-2"
    no-results-title-key="search.no_meetings_found"
    no-results-description-key="search.no_results_criteria"
    empty-state-title-key="search.start_searching_meetings"
    empty-state-description-key="search.meeting_search_description"
    browse-all-key="search.browse_all_meetings"
    @load-more="emit('loadMore')"
    @clear-filters="emit('clearFilters')"
    @retry="emit('retry')"
    @report-error="emit('reportError')"
  >
    <template #skeleton="{ count }">
      <MeetingResultsSkeleton v-for="i in count" :key="i" view-mode="compact" />
    </template>

    <template #item="{ item }">
      <MeetingCompactListItem :meeting="item" />
    </template>
  </BaseSearchResults>
</template>

<script setup lang="ts">
import BaseSearchResults from './Shared/BaseSearchResults.vue';
import MeetingCompactListItem from './MeetingCompactListItem.vue';
import MeetingResultsSkeleton from './MeetingResultsSkeleton.vue';

interface Meeting {
  id: string | number;
  title?: string;
  start_time: number;
  completion_status: string;
  institution_id?: string | number;
  institution_name_lt?: string;
  institution_name_en?: string;
  tenant_shortname?: string;
  agenda_items_count?: number;
  total_agenda_items?: number;
  positive_outcomes?: number;
  negative_outcomes?: number;
  neutral_outcomes?: number;
  student_success_rate?: number;
  [key: string]: any;
}

interface Props {
  results: Meeting[];
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
</script>
