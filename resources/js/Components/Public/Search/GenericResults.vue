<template>
  <BaseSearchResults
    :results
    :is-loading
    :has-query
    :search-query
    :total-hits
    :has-more-results
    :is-loading-more
    :skeleton-count="4"
    loading-container-class="space-y-2"
    results-container-class="space-y-2"
    transition-name="list"
    transition-class="space-y-2"
    :no-results-title-key="noResultsTitleKey"
    no-results-description-key="search.no_results_criteria"
    empty-state-title-key="search.start_search"
    empty-state-description-key="search.search_across_types"
    browse-all-key="search.browse_all"
    @load-more="emit('loadMore')"
  >
    <template #skeleton="{ count }">
      <div v-for="i in count" :key="i" class="h-16 rounded-md border border-border/50 bg-muted/40 animate-pulse" />
    </template>

    <template #item="{ item }">
      <SmartLink :href="getItemUrl(toSearchItem(item))" class="plain block">
        <div
          class="group transition-all duration-200 border border-border/50 rounded-md bg-card hover:shadow-lg hover:bg-accent/20 hover:border-primary/30 cursor-pointer">
          <div class="flex items-center gap-3 px-3 sm:px-4 py-2.5">
            <div
              class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground group-hover:text-primary transition-colors">
              <component :is="getIconComponent(searchType)" class="w-4 h-4" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-medium text-card-foreground group-hover:text-primary transition-colors line-clamp-1">
                {{ displayTitle(item) }}
              </h3>
              <p v-if="displaySnippet(item)" class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                {{ displaySnippet(item) }}
              </p>
            </div>
            <time v-if="displayDate(item)" class="text-xs text-muted-foreground whitespace-nowrap flex-shrink-0">
              {{ displayDate(item) }}
            </time>
            <ArrowRightIcon
              class="w-3.5 h-3.5 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
          </div>
        </div>
      </SmartLink>
    </template>
  </BaseSearchResults>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRightIcon } from 'lucide-vue-next';

import BaseSearchResults from './Shared/BaseSearchResults.vue';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { useSearchUtils, type SearchItem } from '@/Composables/useSearchUtils';

type GenericType = 'news' | 'pages' | 'calendar';

interface Props {
  results: any[];
  /** Maps to the public collection rendered in this section. */
  type: GenericType;
  isLoading?: boolean;
  hasQuery?: boolean;
  searchQuery?: string;
  totalHits?: number;
  hasMoreResults?: boolean;
  isLoadingMore?: boolean;
  noResultsTitleKey?: string;
}

const props = withDefaults(defineProps<Props>(), {
  results: () => [],
  isLoading: false,
  hasQuery: false,
  searchQuery: '',
  totalHits: 0,
  hasMoreResults: false,
  isLoadingMore: false,
  noResultsTitleKey: 'search.no_results_found',
});

const emit = defineEmits<{
  loadMore: [];
}>();

const page = usePage();
const locale = computed(() => (page.props.app as { locale?: string })?.locale || 'lt');

const { getItemUrl, getIconComponent, getItemContent, formatDate, getItemDate } = useSearchUtils();

// useSearchUtils uses 'publicInstitutions' for institutions; the generic types map 1:1.
const searchType = computed<SearchItem['type']>(() => props.type);

const displayTitle = (item: any): string => {
  if (props.type === 'calendar') {
    return (locale.value === 'en' ? item.title_en : item.title_lt) || item.title || item.title_lt || item.title_en || '';
  }
  return item.title || item.name || '';
};

const toSearchItem = (item: any): SearchItem => ({
  ...item,
  type: searchType.value,
  title: displayTitle(item),
});

const displaySnippet = (item: any): string => getItemContent({ ...item, type: props.type });

const displayDate = (item: any): string => formatDate(getItemDate({ ...item, type: props.type }));
</script>
