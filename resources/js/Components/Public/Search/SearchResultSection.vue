<template>
  <div v-if="typeResults && typeResults.results.length > 0">
    <div class="space-y-1.5">
      <!-- Section Header with total hits count -->
      <div :class="getSectionHeaderClasses()">
        {{ icon }} {{ title }} ({{ currentHits }})
      </div>

      <!-- Results -->
      <div class="space-y-2">
        <div v-for="item in typeResults.results" :key="`${item.id}-${type}`" :class="getItemClasses()"
          :title="$t('search.click_to_open')" role="button" tabindex="0" @click="handleItemClick(item, $event)"
          @keydown="handleItemKeydown(item, $event)">
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
              <div :class="getIconClasses()">
                <component :is="getIconComponent(type)" class="w-3 h-3" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2 mb-1">
                <time class="text-xs text-muted-foreground">{{ formatDate(getItemDate(item)) }}</time>
                <!-- Navigation indicator -->
                <IconArrowRight class="w-3 h-3 text-muted-foreground opacity-60 group-hover:opacity-100 transition-opacity" />
              </div>
              <h3 class="font-medium text-sm leading-tight mb-1 group-hover:text-primary transition-colors">
                <AisHighlight attribute="title" :hit="item" />
                <AisHighlight v-if="!item.title && item.name" attribute="name" :hit="item" />
                <span v-if="!item.title && !item.name">{{ item.name || item.title || $t('search.untitled') }}</span>
              </h3>
              <div v-if="getItemContent(item)" class="text-xs text-muted-foreground leading-relaxed line-clamp-2">
                {{ stripHtml(getItemContent(item)) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Load More Button - only show if there are items and more pages available -->
        <div v-if="!typeResults.isLastPage && typeResults.results.length > 0" class="px-3 py-2">
          <Button variant="ghost" size="sm" :class="getLoadMoreClasses()" class="w-full justify-center text-xs"
            @click="typeResults.refineNext">
            <IconArrowRight class="mr-1 w-3.5 h-3.5" />
            {{ $t('search.show_more_type', { type: title.toLowerCase() }) }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, inject } from 'vue';
import { AisHighlight } from 'vue-instantsearch/vue3/es';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { useSearchService, type SearchService } from '@/Composables/useSearchService';
import { useSearchUtils } from '@/Composables/useSearchUtils';
import IconArrowRight from '~icons/fluent/arrow-right-16-filled';

interface Props {
  indexName: string;
  title: string;
  icon: string;
  type: string;
  resultOrder?: 'relevance' | 'date' | 'type';
}

const props = withDefaults(defineProps<Props>(), {
  resultOrder: 'relevance',
});

const emit = defineEmits<{
  navigateToItem: [item: any];
  updateResultCount: [count: number];
  updateTotalHits: [totalHits: number];
}>();

// Use centralized search service - inject from parent or create new
const searchService = inject<SearchService>('searchService') || useSearchService();

// Use shared search utilities
const {
  getIconComponent,
  formatDate,
  stripHtml,
  getItemDate,
  getItemContent,
  getSectionHeaderClasses,
  getItemClasses,
  getIconClasses,
  getLoadMoreClasses,
} = useSearchUtils();

// Get results for this content type from the search service
const typeResults = computed(() => {
  return searchService.getContentTypeResults(props.type);
});

// Current hits from the type results
const currentHits = computed(() => {
  return typeResults.value?.totalHits || 0;
});

// Mobile detection
const isMobile = computed(() => {
  if (typeof window === 'undefined') return false;
  return window.innerWidth < 1024;
});

// Event handlers - simplified for direct navigation
const handleItemClick = (item: any, event: MouseEvent) => {
  const itemWithType = { ...item, type: props.type };
  emit('navigateToItem', itemWithType);
};

const handleItemKeydown = (item: any, event: KeyboardEvent) => {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault();
    emit('navigateToItem', { ...item, type: props.type });
  }
};

// Watch for changes in type results and emit updates
watch(typeResults, (newTypeResults) => {
  if (newTypeResults) {
    emit('updateTotalHits', newTypeResults.totalHits);
    emit('updateResultCount', newTypeResults.results.length);
  }
  else {
    emit('updateTotalHits', 0);
    emit('updateResultCount', 0);
  }
}, { immediate: true });

// All utility functions now imported from useSearchUtils
</script>
