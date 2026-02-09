<template>
  <!-- Hidden collectors for each content type - invisible but functional -->
  <div style="display: none;" aria-hidden="true">
    <AisIndex
      v-for="contentType in enabledContentTypes"
      :key="contentType.id"
      :index-name="contentType.indexName"
    >
      <AisConfigure :hits-per-page.camel="15" />

      <!-- Collect stats for this index -->
      <AisStats>
        <template #default="{ nbHits }">
          <StatsCollector :content-type-id="contentType.id" :nb-hits />
        </template>
      </AisStats>

      <AisInfiniteHits
        :transform-items="(items) => transformItems(items, contentType)"
        :show-previous="false"
      >
        <template #default="{ items, isLastPage, refineNext }">
          <ResultsCollector
            :content-type-id="contentType.id"
            :content-type
            :items
            :is-last-page
            :refine-next
          />
        </template>
      </AisInfiniteHits>
    </AisIndex>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, defineComponent, onUnmounted } from 'vue';
import { AisIndex, AisInfiniteHits, AisConfigure, AisStats } from 'vue-instantsearch/vue3/es';

import type { ContentType } from '@/Composables/useSearchController';

interface Props {
  enabledContentTypes: ContentType[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  updateResults: [contentTypeId: string, contentType: ContentType, results: any[], totalHits: number, isLastPage: boolean, refineNext: () => void];
}>();

// Debounce timers to prevent rapid updates
const debounceTimers = ref<Record<string, ReturnType<typeof setTimeout> | null>>({});

// Store stats separately to get accurate total hits
const statsCache = ref<Record<string, number>>({});

// Transform items to ensure consistent format
const transformItems = (items: any[], contentType: ContentType) => {
  return items.map((item) => {
    // Handle Typesense response structure - data might be in item.document
    const document = item.document || item;

    return {
      ...document,
      ...item, // Keep any meta fields from the outer item
      id: document.id || item.id,
      title: document.title || item.title || document.name || item.name || '',
      type: contentType.id,
      contentType,
    };
  });
};

// Reactive data stores for collecting stats and results
const currentStats = ref<Record<string, number>>({});
const currentResults = ref<Record<string, { items: any[]; isLastPage: boolean; refineNext: () => void }>>({});

// Helper function to update stats safely
const updateStats = (contentTypeId: string, nbHits: number) => {
  // Clear existing timer
  if (debounceTimers.value[`stats_${contentTypeId}`]) {
    clearTimeout(debounceTimers.value[`stats_${contentTypeId}`]!);
  }

  // Set new debounced update
  debounceTimers.value[`stats_${contentTypeId}`] = setTimeout(() => {
    currentStats.value[contentTypeId] = nbHits;
    debounceTimers.value[`stats_${contentTypeId}`] = null;
  }, 100);
};

// Helper function to update results safely
const updateResultsData = (
  contentTypeId: string,
  contentType: ContentType,
  items: any[],
  isLastPage: boolean,
  refineNext: () => void,
) => {
  // Clear existing timer
  const timerKey = `results_${contentTypeId}`;
  if (debounceTimers.value[timerKey]) {
    clearTimeout(debounceTimers.value[timerKey]!);
  }

  // Set new debounced update
  debounceTimers.value[timerKey] = setTimeout(() => {
    // Store the raw results data
    currentResults.value[contentTypeId] = { items, isLastPage, refineNext };

    // Transform items to ensure consistent format
    const transformedItems = transformItems(items, contentType);

    // Get total hits from stats cache, fallback to items.length
    const totalHits = currentStats.value[contentTypeId] || items.length;

    emit('updateResults', contentTypeId, contentType, transformedItems, totalHits, isLastPage, refineNext);
    debounceTimers.value[timerKey] = null;
  }, 100);
};

// Performance-optimized helper components to prevent recursive updates
const StatsCollector = defineComponent({
  props: ['contentTypeId', 'nbHits'],
  setup(props) {
    watch(() => props.nbHits, (newHits) => {
      updateStats(props.contentTypeId, newHits);
    }, { immediate: true });

    return () => null;
  },
});

const ResultsCollector = defineComponent({
  props: ['contentTypeId', 'contentType', 'items', 'isLastPage', 'refineNext'],
  setup(props) {
    watch(() => props.items, (newItems) => {
      updateResultsData(props.contentTypeId, props.contentType, newItems, props.isLastPage, props.refineNext);
    }, { immediate: true, deep: true });

    return () => null;
  },
});

// Cleanup on unmount
onUnmounted(() => {
  Object.values(debounceTimers.value).forEach((timer) => {
    if (timer) clearTimeout(timer);
  });
});
</script>

<style scoped>
/* Ensure this component is completely hidden but functional */
div {
  position: absolute;
  top: -9999px;
  left: -9999px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}
</style>
